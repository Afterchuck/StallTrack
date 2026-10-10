<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_a_period_bill_and_rejects_overlap(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vendor = Vendor::factory()->create(['stall_number' => 'A-01']);
        $data = [
            'vendor_id' => $vendor->id, 'period_start' => '2026-10-01',
            'period_end' => '2026-10-31', 'due_date' => '2026-10-30', 'amount' => '3500.00',
            'paid_amount' => '3500.00', 'created_by' => 9999,
        ];

        $this->actingAs($admin)->post(route('bills.store'), $data)->assertSessionHasNoErrors()->assertRedirect();

        $bill = Bill::firstOrFail();
        $this->assertDatabaseHas('bills', ['id' => $bill->id, 'vendor_id' => $vendor->id, 'stall_number' => 'A-01', 'created_by' => $admin->id, 'paid_amount' => 0]);
        $this->assertSame('Unpaid', $bill->status);
        $this->post(route('bills.store'), [...$data, 'period_start' => '2026-10-15'])->assertSessionHasErrors('period_start');
        $this->assertDatabaseCount('bills', 1);
    }

    public function test_bill_payment_form_explains_that_receipts_are_generated_automatically(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bill = Bill::factory()->create();

        $this->actingAs($admin)->get(route('bills.show', $bill))
            ->assertSee('A unique receipt number will be generated automatically when this payment is confirmed.')
            ->assertDontSee('name="receipt_number"', false);
    }

    public function test_partial_then_full_payment_updates_both_dashboards_and_keeps_next_period_unpaid(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::factory()->create(['user_id' => $user->id]);
        $bill = Bill::factory()->for($vendor)->create(['amount' => '3500.00', 'due_date' => today()->subDay()]);
        $nextBill = Bill::factory()->for($vendor)->create([
            'period_start' => today()->addMonth()->startOfMonth(),
            'period_end' => today()->addMonth()->endOfMonth(),
        ]);

        $this->actingAs($admin)->post(route('bills.payments.store', $bill), $this->paymentData('1000.00'))
            ->assertSessionHasNoErrors()->assertRedirect(route('bills.show', $bill));

        $firstReceipt = Payment::where('bill_id', $bill->id)->value('receipt_number');
        $this->assertMatchesRegularExpression('/^RCT-[0-9A-HJKMNP-TV-Z]{26}$/', $firstReceipt);
        $this->assertSame('Partially paid', $bill->fresh()->status);
        $this->assertSame('2500.00', $bill->fresh()->balance);
        $this->assertTrue($bill->fresh()->is_overdue);
        $this->assertDatabaseHas('payments', ['bill_id' => $bill->id, 'amount' => 1000, 'status' => 'Paid', 'recorded_by' => $admin->id]);
        $this->get(route('bills.show', $bill))->assertOk()->assertSee('Partially paid')->assertSee($firstReceipt);
        $this->get(route('payments'))->assertOk()->assertSee('Partially paid');
        $this->get(route('dashboard'))->assertOk()->assertViewHas('outstandingTotal', 6000)
            ->assertViewHas('collectedMonthly', 1000);
        $this->actingAs($user)->get(route('vendor.dashboard'))->assertOk()->assertSee('Partially paid')->assertSee('2,500.00');
        $firstPayment = Payment::where('receipt_number', $firstReceipt)->firstOrFail();
        $this->get(route('vendor.payments'))
            ->assertOk()
            ->assertSee($firstReceipt)
            ->assertSee('View')
            ->assertSee('data-open-receipt', false)
            ->assertSee('data-receipt-number="'.$firstReceipt.'"', false)
            ->assertSee('data-confirmed-date="'.$firstPayment->paid_at->format('M d, Y').'"', false)
            ->assertSee('VENDOR PAYMENT RECEIPT')
            ->assertSee('id="download-payment-receipt"', false)
            ->assertViewHas('totalPaid', 1000);

        $this->actingAs($admin)->post(route('bills.payments.store', $bill), $this->paymentData('2500.00'))
            ->assertSessionHasNoErrors()->assertRedirect();
        $secondReceipt = Payment::where('bill_id', $bill->id)->where('receipt_number', '!=', $firstReceipt)->value('receipt_number');
        $this->assertNotSame($firstReceipt, $secondReceipt);
        $this->assertSame('Paid', $bill->fresh()->status);
        $this->assertSame('0.00', $bill->fresh()->balance);
        $this->assertSame('Unpaid', $nextBill->fresh()->status);
        $this->actingAs($user)->get(route('vendor.bills.show', $bill))
            ->assertOk()
            ->assertSee('View')
            ->assertSee('data-receipt-number="'.$secondReceipt.'"', false);
        $this->actingAs($admin);
        $this->get(route('payments', ['status' => 'Paid']))
            ->assertViewHas('bills', fn ($bills): bool => $bills->contains('id', $bill->id));
        $this->get(route('dashboard'))->assertViewHas('outstandingBills', fn ($bills): bool => ! $bills->contains('id', $bill->id));
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseCount('activity_logs', 2);
    }

    public function test_receipts_are_generated_uniquely_and_overpayment_cannot_change_the_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bill = Bill::factory()->create(['amount' => '100.00']);
        $this->actingAs($admin)->post(route('bills.payments.store', $bill), [...$this->paymentData('40.00'), 'receipt_number' => 'CLIENT-CHOSEN'])
            ->assertSessionHasNoErrors();

        $firstReceipt = Payment::firstOrFail()->receipt_number;
        $this->assertMatchesRegularExpression('/^RCT-[0-9A-HJKMNP-TV-Z]{26}$/', $firstReceipt);
        $this->assertNotSame('CLIENT-CHOSEN', $firstReceipt);

        $this->post(route('bills.payments.store', $bill), [...$this->paymentData('40.00'), 'receipt_number' => 'CLIENT-CHOSEN'])
            ->assertSessionHasNoErrors();
        $secondReceipt = Payment::where('receipt_number', '!=', $firstReceipt)->value('receipt_number');
        $this->assertMatchesRegularExpression('/^RCT-[0-9A-HJKMNP-TV-Z]{26}$/', $secondReceipt);
        $this->assertNotSame($firstReceipt, $secondReceipt);
        $this->post(route('bills.payments.store', $bill), $this->paymentData('20.01'))->assertSessionHasErrors('amount');

        $this->assertSame('20.00', $bill->fresh()->balance);
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseCount('activity_logs', 2);
    }

    public function test_centavo_installments_settle_exactly_and_paid_bills_reject_more_money(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bill = Bill::factory()->create(['amount' => '0.30']);

        $this->actingAs($admin)->post(route('bills.payments.store', $bill), $this->paymentData('0.10'))->assertSessionHasNoErrors();
        $this->post(route('bills.payments.store', $bill), $this->paymentData('0.20'))->assertSessionHasNoErrors();

        $this->assertSame('0.00', $bill->fresh()->balance);
        $this->assertSame('Paid', $bill->fresh()->status);
        $this->post(route('bills.payments.store', $bill), $this->paymentData('0.01'))->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_reversal_restores_the_balance_once_and_retains_audit_history(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create(['role' => 'admin']);
        $bill = Bill::factory()->create(['amount' => '100.00']);
        $this->actingAs($admin)->post(route('bills.payments.store', $bill), $this->paymentData('100.00'))->assertSessionHasNoErrors();
        $payment = Payment::firstOrFail();

        $this->patch(route('bills.payments.reverse', [$bill, $payment]), ['reversal_reason' => str_repeat('Correction. ', 30)])
            ->assertSessionHasNoErrors()->assertRedirect(route('bills.show', $bill));

        $this->assertSame('Unpaid', $bill->fresh()->status);
        $this->assertSame('100.00', $bill->fresh()->balance);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'Reversed', 'reversed_by' => $admin->id, 'reversed_at' => now()->toDateTimeString()]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'payment_reversed', 'subject_id' => $bill->id]);
        $this->get(route('dashboard'))->assertViewHas('collectedMonthly', 0);
        $this->patch(route('bills.payments.reverse', [$bill, $payment]), ['reversal_reason' => 'Again'])->assertSessionHasErrors();
        $this->patch(route('vendors.payments.paid', [$bill->vendor, $payment]))->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('100.00', $bill->fresh()->balance);
    }

    public function test_existing_receipt_is_applied_once_without_double_counting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bill = Bill::factory()->create();
        $payment = Payment::create([
            'vendor_id' => $bill->vendor_id, 'vendor_name' => $bill->vendor_name,
            'amount' => '500.00', 'paid_at' => today(), 'receipt_number' => 'LEGACY', 'status' => 'Paid',
        ]);

        $this->actingAs($admin)->post(route('bills.receipts.allocate', $bill), ['payment_id' => $payment->id, 'confirmed' => 1])
            ->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('3000.00', $bill->fresh()->balance);
        $this->assertDatabaseCount('payments', 1);
        $this->get(route('payments'))->assertViewHas('paidTotal', 500);
        $this->post(route('bills.receipts.allocate', $bill), ['payment_id' => $payment->id, 'confirmed' => 1])
            ->assertSessionHasErrors('payment_id');
        $this->assertSame('3000.00', $bill->fresh()->balance);
    }

    public function test_receipts_from_another_vendor_or_bill_cannot_be_applied_or_reversed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bill = Bill::factory()->create();
        $other = Bill::factory()->create();
        $this->actingAs($admin)->post(route('bills.payments.store', $other), $this->paymentData('100.00'))->assertSessionHasNoErrors();
        $payment = Payment::firstOrFail();

        $this->post(route('bills.receipts.allocate', $bill), ['payment_id' => $payment->id, 'confirmed' => 1])->assertNotFound();
        $this->patch(route('bills.payments.reverse', [$bill, $payment]), ['reversal_reason' => 'Wrong account'])->assertNotFound();

        $this->assertSame('3500.00', $bill->fresh()->balance);
        $this->assertSame('3400.00', $other->fresh()->balance);
    }

    #[DataProvider('invalidPayments')]
    public function test_invalid_payment_is_rejected(array $invalid, string $field): void
    {
        $this->freezeTime();
        $admin = User::factory()->create(['role' => 'admin']);
        $bill = Bill::factory()->create();

        $this->actingAs($admin)->post(route('bills.payments.store', $bill), [...$this->paymentData('100.00'), ...$invalid])
            ->assertSessionHasErrors($field);

        $this->assertSame('3500.00', $bill->fresh()->balance);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public static function invalidPayments(): array
    {
        return [
            'zero' => [['amount' => '0'], 'amount'],
            'negative' => [['amount' => '-0.01'], 'amount'],
            'too precise' => [['amount' => '1.001'], 'amount'],
            'exponent' => [['amount' => '1e2'], 'amount'],
            'future' => [['paid_at' => '2099-01-01'], 'paid_at'],
            'method' => [['payment_method' => 'Unknown'], 'payment_method'],
            'unconfirmed' => [['confirmed' => 0], 'confirmed'],
        ];
    }

    public function test_vendors_cannot_manage_bills_or_see_other_vendors_balances(): void
    {
        $user = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::factory()->create(['user_id' => $user->id]);
        $bill = Bill::factory()->for($vendor)->create();
        $other = Bill::factory()->create(['vendor_name' => 'Private vendor']);
        $payment = Payment::create([
            'vendor_id' => $other->vendor_id, 'bill_id' => $other->id, 'vendor_name' => 'Private vendor',
            'receipt_number' => 'PRIVATE-RECEIPT', 'amount' => 10, 'paid_at' => today(), 'status' => 'Paid',
        ]);
        $this->actingAs($user)->get(route('payments'))->assertForbidden();
        $this->get(route('bills.show', $bill))->assertForbidden();
        $this->post(route('bills.store'), [])->assertForbidden();
        $this->post(route('bills.payments.store', $bill), $this->paymentData('100'))->assertForbidden();
        $this->post(route('bills.receipts.allocate', $bill), ['payment_id' => $payment->id])->assertForbidden();
        $this->patch(route('bills.payments.reverse', [$other, $payment]), ['reversal_reason' => 'No'])->assertForbidden();
        $this->get(route('vendor.payments', ['vendor_id' => $other->vendor_id]))->assertOk()
            ->assertDontSee('PRIVATE-RECEIPT')->assertViewHas('bills', fn ($bills): bool => $bills->count() === 1 && $bills->first()->id === $bill->id);
    }

    public function test_unlinked_vendor_does_not_inherit_records_by_name_or_null_vendor_id(): void
    {
        $user = User::factory()->create(['role' => 'vendor', 'name' => 'Shared name']);
        $otherVendor = Vendor::factory()->create(['name' => 'Shared name']);
        Bill::factory()->for($otherVendor)->create();
        Payment::create(['vendor_name' => 'Shared name', 'receipt_number' => 'UNLINKED', 'paid_at' => today(), 'amount' => 900, 'status' => 'Paid']);

        $this->actingAs($user)->get(route('vendor.dashboard'))->assertViewHas('bills', fn ($bills): bool => $bills->isEmpty())->assertDontSee('UNLINKED');
        $this->get(route('vendor.payments'))->assertViewHas('totalPaid', 0)->assertDontSee('UNLINKED');
    }

    public function test_guest_is_redirected_and_billed_vendor_cannot_be_deleted(): void
    {
        $bill = Bill::factory()->create();
        $this->get(route('bills.show', $bill))->assertRedirect(route('login'));
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->delete(route('vendors.destroy', $bill->vendor))->assertSessionHasErrors('vendor');
        $this->assertModelExists($bill);
        $this->assertModelExists($bill->vendor);
    }

    public function test_reversal_requires_a_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bill = Bill::factory()->create();
        $this->actingAs($admin)->post(route('bills.payments.store', $bill), $this->paymentData('100'))->assertSessionHasNoErrors();
        $payment = Payment::firstOrFail();

        $this->patch(route('bills.payments.reverse', [$bill, $payment]), [])->assertSessionHasErrors('reversal_reason');

        $this->assertSame('3400.00', $bill->fresh()->balance);
        $this->assertSame('Paid', $payment->fresh()->status);
    }

    /** @return array{amount: string, paid_at: string, payment_method: string, confirmed: int} */
    private function paymentData(string $amount): array
    {
        return [
            'amount' => $amount, 'paid_at' => today()->toDateString(),
            'payment_method' => 'Cash', 'confirmed' => 1,
        ];
    }
}
