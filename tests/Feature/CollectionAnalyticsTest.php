<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Stall;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CollectionAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_chart_counts_confirmed_installments_exactly_and_fills_empty_days(): void
    {
        $vendor = Vendor::factory()->create();
        $bill = Bill::factory()->for($vendor)->create(['amount' => '100.00', 'paid_amount' => '0.30']);
        Payment::factory()->for($vendor)->for($bill)->create(['paid_at' => '2026-10-01', 'amount' => '0.10']);
        Payment::factory()->for($vendor)->for($bill)->create(['paid_at' => '2026-10-01', 'amount' => '0.20']);
        Payment::factory()->for($vendor)->create(['paid_at' => '2026-10-03', 'amount' => '500.00']);
        Payment::factory()->for($vendor)->create(['paid_at' => '2026-10-02', 'status' => 'Recorded']);
        Payment::factory()->for($vendor)->create(['paid_at' => '2026-10-02', 'status' => 'Reversed', 'reversed_at' => now()]);
        Payment::factory()->for($vendor)->create(['paid_at' => '2026-09-30', 'amount' => '900.00']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $response = $this->get(route('payments', ['from' => '2026-10-01', 'to' => '2026-10-03']));

        $response->assertOk()->assertSee('View exact chart values')->assertViewHas('chart', fn (array $chart): bool => $chart['total'] === '500.30' && $chart['count'] === 3
            && array_column($chart['points'], 'cents') === [50000, 0, 30]
            && $chart['points'][0]['label'] === 'Oct 03, 2026'
            && $chart['points'][2]['label'] === 'Oct 01, 2026'
        );
        $this->assertDatabaseCount('payments', 6);
        $this->assertSame('99.70', $bill->fresh()->balance);
    }

    #[DataProvider('groupings')]
    public function test_chart_groups_by_calendar_period(string $group, string $from, string $to, array $dates, array $expected): void
    {
        foreach ($dates as $date) {
            Payment::factory()->create(['paid_at' => $date, 'amount' => '10.00']);
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(route('payments', compact('group', 'from', 'to')))->assertOk()
            ->assertViewHas('chart', fn (array $chart): bool => array_column($chart['points'], 'cents') === $expected);
    }

    public static function groupings(): array
    {
        return [
            'Sunday then Monday' => ['weekly', '2026-10-04', '2026-10-05', ['2026-10-04', '2026-10-05'], [1000, 1000]],
            'ISO week crosses year' => ['weekly', '2026-12-31', '2027-01-04', ['2026-12-31', '2027-01-01', '2027-01-04'], [1000, 2000]],
            'months including empty month' => ['monthly', '2026-01-01', '2026-03-31', ['2026-01-31', '2026-03-01'], [1000, 0, 1000]],
            'years including empty year' => ['yearly', '2024-01-01', '2026-12-31', ['2024-02-29', '2026-12-31'], [1000, 0, 1000]],
        ];
    }

    public function test_default_chart_ranges_and_no_payment_message(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 4)->startOfDay());
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['daily' => 30, 'weekly' => 12, 'monthly' => 12, 'yearly' => 5] as $group => $count) {
            $this->get(route('payments', compact('group')))->assertSee('No confirmed payments match these filters.')
                ->assertViewHas('chart', fn (array $chart): bool => count($chart['points']) === $count && $chart['total'] === '0.00');
        }
    }

    public function test_vendor_receipt_search_and_method_filter_chart_and_receipts_together(): void
    {
        $vendor = Vendor::factory()->create();
        Payment::factory()->for($vendor)->create(['receipt_number' => 'FIND-100', 'amount' => '25.00', 'paid_at' => '2026-10-01']);
        Payment::factory()->for($vendor)->create(['receipt_number' => 'FIND-200', 'payment_method' => 'Bank transfer', 'paid_at' => '2026-10-01']);
        Payment::factory()->create(['receipt_number' => 'FIND-300', 'paid_at' => '2026-10-01']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(route('payments', ['q' => 'FIND', 'vendor_id' => $vendor->id, 'method' => 'Cash', 'from' => '2026-10-01', 'to' => '2026-10-01']))
            ->assertViewHas('chart', fn (array $chart): bool => $chart['total'] === '25.00')
            ->assertViewHas('receipts', fn ($receipts): bool => $receipts->total() === 1 && $receipts->first()->receipt_number === 'FIND-100');
    }

    public function test_bill_filters_do_not_hide_payments_from_the_chart_and_pagination_keeps_filters(): void
    {
        $vendor = Vendor::factory()->create();
        for ($index = 1; $index <= 12; $index++) {
            Bill::factory()->for($vendor)->create(['vendor_name' => 'Find Me', 'period_start' => '2026-01-01', 'period_end' => '2026-01-31', 'due_date' => '2026-10-10', 'amount' => '100.00']);
        }
        Bill::factory()->for($vendor)->create(['vendor_name' => 'Find Me', 'due_date' => '2026-11-01']);
        Payment::factory()->for($vendor)->create(['vendor_name' => 'Find Me', 'paid_at' => '2026-10-01']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $filters = ['vendor_id' => $vendor->id, 'q' => 'Find', 'status' => 'Unpaid', 'due_from' => '2026-10-01', 'due_to' => '2026-10-31', 'from' => '2026-10-01', 'to' => '2026-10-31', 'sort' => 'newest'];

        $response = $this->get(route('payments', $filters));

        $response->assertViewHas('bills', fn ($bills): bool => $bills->total() === 12 && str_contains($bills->nextPageUrl(), 'q=Find'))
            ->assertViewHas('chart', fn (array $chart): bool => $chart['total'] === '100.00');
        $this->get(route('payments', $filters + ['page' => 2]))->assertViewHas('bills', fn ($bills): bool => $bills->count() === 2);
    }

    public function test_receipt_status_assignment_and_unspecified_method_filters(): void
    {
        $bill = Bill::factory()->create();
        Payment::factory()->for($bill->vendor)->for($bill)->create(['paid_at' => '2026-10-01', 'payment_method' => null]);
        Payment::factory()->create(['paid_at' => '2026-10-01', 'payment_method' => null, 'status' => 'Reversed']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(route('payments', ['method' => 'Unspecified', 'assignment' => 'unassigned', 'receipt_status' => 'Reversed', 'from' => '2026-10-01', 'to' => '2026-10-01']))
            ->assertViewHas('receipts', fn ($receipts): bool => $receipts->total() === 1 && $receipts->first()->status === 'Reversed')
            ->assertViewHas('chart', fn (array $chart): bool => $chart['total'] === '100.00');
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_collection_filters_are_rejected(array $filters, string $field): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('payments', $filters))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('payments', 0);
    }

    public static function invalidFilters(): array
    {
        return [
            'group' => [['group' => 'hourly'], 'group'],
            'bad date' => [['from' => '2026-02-30'], 'from'],
            'date order' => [['from' => '2026-11-01', 'to' => '2026-10-01'], 'from'],
            'too many points' => [['group' => 'daily', 'from' => '2026-01-01', 'to' => '2026-12-31'], 'from'],
            'sort injection' => [['sort' => 'amount desc; DROP TABLE bills'], 'sort'],
            'due date order' => [['due_from' => '2026-11-01', 'due_to' => '2026-10-01'], 'due_to'],
            'search array' => [['q' => ['invalid']], 'q'],
            'method' => [['method' => 'Invalid'], 'method'],
        ];
    }

    public function test_search_treats_wildcards_literally_and_escapes_output(): void
    {
        Payment::factory()->create(['receipt_number' => '100%_MATCH', 'paid_at' => '2026-10-01']);
        Payment::factory()->create(['receipt_number' => '100OTHER', 'paid_at' => '2026-10-01']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('payments', ['q' => '%_', 'from' => '2026-10-01', 'to' => '2026-10-01']))
            ->assertViewHas('receipts', fn ($receipts): bool => $receipts->total() === 1);
        $this->get(route('payments', ['q' => '<script>alert(1)</script>']))
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_directory_and_rental_billing_filters_combine(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Target Vendor', 'market_section' => 'Dry', 'status' => 'Inactive']);
        Vendor::factory()->create(['name' => 'Target Other', 'market_section' => 'Wet']);
        $stall = Stall::factory()->create(['market_section' => 'Dry', 'status' => 'Occupied']);
        $rental = Rental::factory()->for($vendor)->for($stall)->create(['billing_cycle' => 'Weekly', 'contract_number' => 'FIND-CONTRACT']);
        Rental::factory()->create(['billing_cycle' => 'Monthly']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(route('vendors.index', ['search' => 'Target', 'stall_type' => 'Dry', 'contract_status' => 'Inactive']))
            ->assertViewHas('vendors', fn ($vendors): bool => $vendors->total() === 1 && $vendors->first()->id === $vendor->id);
        $this->get(route('stalls', ['section' => 'Dry', 'status' => 'Occupied']))
            ->assertViewHas('stalls', fn ($stalls): bool => $stalls->total() === 1);
        $this->get(route('rentals', ['search' => 'FIND', 'cycle' => 'Weekly', 'status' => 'Active']))
            ->assertViewHas('rentals', fn ($rentals): bool => $rentals->total() === 1 && $rentals->first()->id === $rental->id);
        $this->get(route('rental-billing', ['search' => 'Target', 'cycle' => 'Weekly', 'billing_date' => '2026-10-04']))
            ->assertViewHas('rentals', fn ($rentals): bool => $rentals->total() === 1 && $rentals->first()->id === $rental->id);
    }

    public function test_announcements_search_visibility_and_pin_filters(): void
    {
        $this->freezeTime();
        Announcement::factory()->published()->create(['title' => 'Market cleanup', 'is_pinned' => true]);
        Announcement::factory()->create(['title' => 'Market draft', 'is_pinned' => true]);
        Announcement::factory()->published()->create(['title' => 'Market expired', 'expires_at' => now()->subDay()]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(route('announcements', ['search' => 'Market', 'visibility' => 'Published', 'pinned' => '1']))
            ->assertViewHas('announcements', fn ($posts): bool => $posts->total() === 1 && $posts->first()->title === 'Market cleanup');
        $this->get(route('announcements', ['visibility' => 'Draft']))->assertViewHas('announcements', fn ($posts): bool => $posts->total() === 1);
        $this->get(route('announcements', ['visibility' => 'Expired']))->assertViewHas('announcements', fn ($posts): bool => $posts->total() === 1);
    }

    public function test_dashboard_and_due_dates_search_leave_market_totals_unchanged(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 4)->startOfDay());
        $match = Bill::factory()->create(['vendor_name' => 'Target', 'due_date' => '2026-10-04', 'amount' => 100]);
        Bill::factory()->create(['vendor_name' => 'Other', 'due_date' => '2026-10-03', 'amount' => 200]);
        Rental::factory()->create(['end_date' => '2026-10-20', 'contract_number' => 'Target']);
        Rental::factory()->create(['end_date' => '2026-12-20']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(route('dashboard', ['search' => 'Target']))
            ->assertViewHas('outstandingTotal', 300)
            ->assertViewHas('outstandingBills', fn ($bills): bool => $bills->count() === 1 && $bills->first()->id === $match->id);
        $this->get(route('due-dates', ['search' => 'Target', 'days' => 30, 'due_status' => 'today']))
            ->assertViewHas('expiringRentals', fn ($rentals): bool => $rentals->total() === 1)
            ->assertViewHas('overdueBills', fn ($bills): bool => $bills->total() === 1);
    }

    public function test_reports_filter_income_and_activity_by_date_and_section_statistics(): void
    {
        $vendor = Vendor::factory()->create(['market_section' => 'Dry']);
        Stall::factory()->create(['market_section' => 'Dry']);
        Stall::factory()->create(['market_section' => 'Wet']);
        Payment::factory()->for($vendor)->create(['paid_at' => '2026-10-01', 'amount' => 20]);
        Payment::factory()->for($vendor)->create(['paid_at' => '2026-09-01', 'amount' => 40]);
        Payment::factory()->create(['paid_at' => '2026-10-01', 'amount' => 80]);
        $admin = User::factory()->create(['role' => 'admin']);
        ActivityLog::create(['user_id' => $admin->id, 'action' => 'bill_sent', 'description' => 'Target activity', 'created_at' => '2026-10-01 12:00:00']);
        $this->actingAs($admin);

        $this->get(route('reports', ['section' => 'Dry', 'from' => '2026-10-01', 'to' => '2026-10-31', 'search' => 'Target', 'action' => 'bill_sent']))
            ->assertViewHas('paidIncome', 20)->assertViewHas('stallCounts', fn (array $counts): bool => $counts['total'] === 1)
            ->assertViewHas('recentActivity', fn ($logs): bool => $logs->total() === 1);
        $this->get(route('reports', ['from' => '2026-11-01', 'to' => '2026-10-01']))->assertSessionHasErrors('to');
    }

    public function test_guest_and_vendor_cannot_access_admin_analytics(): void
    {
        $this->get(route('payments'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'vendor']));
        foreach (['payments', 'reports', 'due-dates', 'announcements', 'rental-billing'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
    }

    public function test_all_bills_selection_is_preserved_when_other_filters_change(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Bill::factory()->create(['paid_amount' => '3500.00', 'amount' => '3500.00']);

        $this->get(route('payments', ['status' => '']))->assertOk()
            ->assertSee('name="status" value=""', false)
            ->assertViewHas('bills', fn ($bills): bool => $bills->total() === 1);
    }
}
