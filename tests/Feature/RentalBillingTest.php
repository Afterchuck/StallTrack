<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RentalBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_is_read_only_and_send_uses_contract_values_and_notifies_once(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 4)->startOfDay());
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::factory()->for($user)->create(['monthly_rent' => '1.00']);
        $rental = Rental::factory()->for($vendor)->create();

        $this->actingAs($admin)->get(route('rental-billing'))->assertOk()->assertSee($vendor->name)->assertSee($rental->stall->stall_number);
        $data = $this->reviewData($rental);
        $this->assertDatabaseCount('bills', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->post(route('rental-billing.send', $rental), $data + [
            'amount' => '1.00', 'paid_amount' => '3500.00', 'vendor_id' => 9999,
            'period_end' => '2099-12-31',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $bill = Bill::firstOrFail();
        $this->assertSame('3500.00', $bill->amount);
        $this->assertSame('Unpaid', $bill->status);
        $this->assertSame('2026-10-31', $bill->period_end->toDateString());
        $this->assertSame('2026-10-10', $bill->due_date->toDateString());
        $this->assertSame(9, $rental->fresh()->billing_due_days);
        $this->assertDatabaseHas('bills', ['id' => $bill->id, 'rental_id' => $rental->id, 'contract_number' => $rental->contract_number, 'created_by' => $admin->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'bill_sent', 'subject_id' => $bill->id]);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('3500.00', $vendor->notifications()->firstOrFail()->data['balance']);
        $this->post(route('rental-billing.send', $rental), $data)->assertSessionHasErrors('billing_date');
        $this->assertDatabaseCount('bills', 1);
        $this->assertDatabaseCount('notifications', 1);
        $this->get(route('rental-billing'))->assertSee('Sent')->assertSee('View bill / notify');

        $this->actingAs($user)->get(route('vendor.dashboard'))->assertOk()->assertSee('Notifications, 1 unread')->assertSee('3,500.00');
        $this->get(route('vendor.notifications'))->assertOk()->assertSee('Your rental bill is ready')->assertSee('2026-10-10');
        $notice = $vendor->notifications()->firstOrFail();
        $this->post(route('vendor.notifications.read', $notice->id))->assertRedirect(route('vendor.bills.show', $bill));
        $this->assertNotNull($notice->fresh()->read_at);
        $this->get(route('vendor.bills.show', $bill))->assertOk()->assertSee($rental->contract_number)->assertSee('Notifications, 0 unread');
    }

    #[DataProvider('cycles')]
    public function test_periods_follow_contract_anchor_and_cycle(string $cycle, string $start, string $date, string $expectedStart, string $expectedEnd): void
    {
        $rental = Rental::factory()->create(['billing_cycle' => $cycle, 'start_date' => $start, 'end_date' => '2027-12-31']);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('rental-billing.preview', ['rental' => $rental, 'billing_date' => $date]))
            ->assertOk()->assertViewHas('period', fn (array $period): bool => $period['start']->toDateString() === $expectedStart && $period['end']->toDateString() === $expectedEnd
            );
        $this->assertDatabaseCount('bills', 0);
    }

    public static function cycles(): array
    {
        return [
            'monthly before anniversary' => ['Monthly', '2026-01-15', '2026-10-04', '2026-09-15', '2026-10-14'],
            'month end clamp' => ['Monthly', '2026-01-31', '2026-02-28', '2026-02-28', '2026-03-30'],
            'month end restored' => ['Monthly', '2026-01-31', '2026-03-31', '2026-03-31', '2026-04-29'],
            'quarterly' => ['Quarterly', '2026-01-15', '2026-10-04', '2026-07-15', '2026-10-14'],
            'weekly' => ['Weekly', '2026-10-01', '2026-10-09', '2026-10-08', '2026-10-14'],
            'biweekly' => ['Bi-weekly', '2026-10-01', '2026-10-16', '2026-10-15', '2026-10-28'],
        ];
    }

    public function test_multiple_stalls_can_have_separate_bills_for_the_same_vendor_period(): void
    {
        $vendor = Vendor::factory()->create();
        $first = Rental::factory()->for($vendor)->create();
        $second = Rental::factory()->for($vendor)->create(['rent_amount' => '2000.00']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post(route('rental-billing.send', $first), $this->reviewData($first))->assertSessionHasNoErrors();
        $this->post(route('rental-billing.send', $second), $this->reviewData($second))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('bills', 2);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('bills', ['rental_id' => $second->id, 'amount' => 2000]);
    }

    public function test_legacy_bill_blocks_overlapping_send_without_creating_debt_again(): void
    {
        $rental = Rental::factory()->create();
        Bill::factory()->for($rental->vendor)->create(['period_start' => '2026-10-01', 'period_end' => '2026-10-31']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $data = $this->reviewData($rental);

        $this->post(route('rental-billing.send', $rental), $data)->assertSessionHasErrors('billing_date');

        $this->assertDatabaseCount('bills', 1);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_changed_contract_requires_a_new_preview_and_issued_amounts_remain_unchanged(): void
    {
        $rental = Rental::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $data = $this->reviewData($rental);
        $rental->update(['rent_amount' => '4000.00']);

        $this->post(route('rental-billing.send', $rental), $data)->assertSessionHasErrors('review_token');
        $this->assertDatabaseCount('bills', 0);
        $this->assertDatabaseCount('notifications', 0);

        $this->post(route('rental-billing.send', $rental), $this->reviewData($rental))->assertSessionHasNoErrors();
        $rental->update(['rent_amount' => '5000.00']);
        $this->assertSame('4000.00', Bill::firstOrFail()->amount);
    }

    #[DataProvider('invalidContracts')]
    public function test_unsuitable_contracts_cannot_create_bills(array $attributes): void
    {
        $rental = Rental::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $data = $this->reviewData($rental);
        $rental->update($attributes);
        if ($rental->status === 'Active') {
            $response = $this->get(route('rental-billing.preview', ['rental' => $rental, 'billing_date' => '2026-10-04']));
            if ($response->isOk()) {
                $data['review_token'] = $response->viewData('reviewToken');
            }
        }

        $this->post(route('rental-billing.send', $rental), $data)->assertSessionHasErrors();

        $this->assertDatabaseCount('bills', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public static function invalidContracts(): array
    {
        return [
            'inactive' => [['status' => 'Expired']],
            'shortened final period' => [['end_date' => '2026-10-15']],
            'outside contract' => [['end_date' => '2026-09-30']],
            'unknown cycle' => [['billing_cycle' => 'Unknown']],
            'zero rent' => [['rent_amount' => '0.00']],
        ];
    }

    public function test_send_requires_review_confirmation_and_valid_deadline(): void
    {
        $rental = Rental::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post(route('rental-billing.send', $rental), [])->assertSessionHasErrors(['billing_date', 'due_days', 'confirmed', 'review_token']);
        $this->post(route('rental-billing.send', $rental), array_replace($this->reviewData($rental), ['due_days' => -1]))->assertSessionHasErrors('due_days');
        $this->post(route('rental-billing.send', $rental), array_replace($this->reviewData($rental), ['due_days' => 366]))->assertSessionHasErrors('due_days');
        $this->assertDatabaseCount('bills', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_reminder_uses_current_partial_balance_without_another_bill_and_has_cooldown(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create(['role' => 'admin']);
        $bill = Bill::factory()->create(['amount' => '3500.00', 'sent_at' => now()->subDays(2)]);
        $this->actingAs($admin)->post(route('bills.payments.store', $bill), [
            'amount' => '1000.00', 'paid_at' => today()->toDateString(), 'receipt_number' => 'PART-REMINDER',
            'payment_method' => 'Cash', 'confirmed' => 1,
        ])->assertSessionHasNoErrors();

        $this->post(route('bills.notify', $bill), ['confirmed' => 1])->assertSessionHasNoErrors();

        $notice = $bill->vendor->notifications()->firstOrFail();
        $this->assertSame('Rent payment reminder', $notice->data['title']);
        $this->assertSame('2500.00', $notice->data['balance']);
        $this->assertNotNull($bill->fresh()->last_reminded_at);
        $this->post(route('bills.notify', $bill), ['confirmed' => 1])->assertSessionHasErrors('bill');
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('bills', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('2500.00', $bill->fresh()->balance);
    }

    public function test_existing_manual_bill_can_be_notified_but_paid_bills_cannot(): void
    {
        $bill = Bill::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post(route('bills.notify', $bill), [])->assertSessionHasErrors('confirmed');
        $this->post(route('bills.notify', $bill), ['confirmed' => 1])->assertSessionHasNoErrors();
        $this->assertSame('Your rental bill is ready', $bill->vendor->notifications()->firstOrFail()->data['title']);
        $this->assertNotNull($bill->fresh()->sent_at);
        $bill->update(['paid_amount' => $bill->amount]);
        $this->post(route('bills.notify', $bill), ['confirmed' => 1])->assertSessionHasErrors('bill');
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_vendor_cannot_send_or_access_another_vendors_bill_or_notification(): void
    {
        $rental = Rental::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('rental-billing.send', $rental), $this->reviewData($rental))->assertSessionHasNoErrors();
        $bill = Bill::firstOrFail();
        $notice = $rental->vendor->notifications()->firstOrFail();
        $other = User::factory()->create(['role' => 'vendor']);
        Vendor::factory()->for($other)->create();

        $this->actingAs($other)->get(route('rental-billing'))->assertForbidden();
        $this->get(route('rental-billing.preview', ['rental' => $rental, 'billing_date' => '2026-10-04']))->assertForbidden();
        $this->post(route('rental-billing.send', $rental), [])->assertForbidden();
        $this->post(route('bills.notify', $bill), ['confirmed' => 1])->assertForbidden();
        $this->get(route('vendor.bills.show', $bill))->assertNotFound();
        $this->post(route('vendor.notifications.read', $notice->id))->assertNotFound();
        $this->get(route('vendor.notifications'))->assertDontSee('Your rental bill is ready');
        $this->assertNull($notice->fresh()->read_at);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_guests_cannot_use_billing_or_vendor_notifications(): void
    {
        $this->get(route('rental-billing'))->assertRedirect(route('login'));
        $this->get(route('vendor.notifications'))->assertRedirect(route('login'));
        $this->post('/rental-billing/1/send', [])->assertRedirect(route('login'));
        $this->post('/bills/1/notify', [])->assertRedirect(route('login'));
        $this->post('/vendor-notifications/missing/read', [])->assertRedirect(route('login'));
        $this->get('/vendor-bills/1')->assertRedirect(route('login'));
    }

    public function test_vendor_without_a_linked_profile_gets_empty_notifications(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'vendor']))
            ->get(route('vendor.notifications'))->assertOk()->assertSee('No billing notifications yet.');
    }

    public function test_manual_short_period_is_linked_to_its_stall_and_rejects_wrong_vendor_or_dates(): void
    {
        $rental = Rental::factory()->create(['end_date' => '2026-10-15']);
        $other = Vendor::factory()->create();
        $data = ['vendor_id' => $rental->vendor_id, 'rental_id' => $rental->id,
            'period_start' => '2026-10-01', 'period_end' => '2026-10-15', 'due_date' => '2026-10-15', 'amount' => '1500.00'];
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post(route('bills.store'), array_replace($data, ['vendor_id' => $other->id]))->assertSessionHasErrors('rental_id');
        $this->post(route('bills.store'), array_replace($data, ['period_end' => '2026-10-31']))->assertSessionHasErrors('rental_id');
        $this->assertDatabaseCount('bills', 0);

        $this->post(route('bills.store'), $data)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bills', ['rental_id' => $rental->id, 'amount' => 1500, 'stall_number' => $rental->stall->stall_number, 'sent_at' => null]);
        $this->assertDatabaseCount('notifications', 0);
        $this->post(route('bills.store'), $data)->assertSessionHasErrors('period_start');
        $this->assertDatabaseCount('bills', 1);
    }

    public function test_listing_filters_contracts_by_date_and_vendor_and_escapes_names(): void
    {
        $rental = Rental::factory()->create();
        $rental->vendor->update(['name' => '<script>alert(1)</script>']);
        $other = Rental::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(route('rental-billing', ['billing_date' => '2026-10-04', 'vendor_id' => $rental->vendor_id]))
            ->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertViewHas('rentals', fn ($rentals): bool => $rentals->count() === 1 && $rentals->first()->id === $rental->id);
        $this->get(route('rental-billing', ['billing_date' => '2028-01-01']))->assertSee('No active rental contracts cover this date.');
        $this->get(route('rental-billing', ['billing_date' => 'invalid']))->assertSessionHasErrors('billing_date');
    }

    public function test_earlier_debt_is_shown_separately_and_not_added_to_new_rent(): void
    {
        $rental = Rental::factory()->create();
        Bill::factory()->for($rental->vendor)->create([
            'period_start' => '2026-09-01', 'period_end' => '2026-09-30',
            'amount' => '3500.00', 'paid_amount' => '1000.00',
        ]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('rental-billing.preview', ['rental' => $rental, 'billing_date' => '2026-10-04']))
            ->assertViewHas('arrears', '2500.00');

        $this->post(route('rental-billing.send', $rental), $this->reviewData($rental))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bills', ['rental_id' => $rental->id, 'amount' => 3500, 'paid_amount' => 0]);
        $this->assertDatabaseCount('bills', 2);
    }

    /** @return array{billing_date: string, due_days: int, review_token: string, confirmed: int} */
    private function reviewData(Rental $rental): array
    {
        $preview = $this->get(route('rental-billing.preview', ['rental' => $rental, 'billing_date' => '2026-10-04']))->assertOk();

        return ['billing_date' => '2026-10-04', 'due_days' => 9, 'review_token' => $preview->viewData('reviewToken'), 'confirmed' => 1];
    }
}
