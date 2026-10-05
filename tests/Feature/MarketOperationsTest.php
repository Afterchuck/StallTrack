<?php

namespace Tests\Feature;

use App\Models\Rental;
use App\Models\Stall;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_rental_modal_explains_empty_inventory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('rentals'))
            ->assertOk()
            ->assertSee('No stalls are available for a new rental.')
            ->assertSee('Manage stalls');
    }

    public function test_admin_can_create_a_rental_from_the_modal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vendor = Vendor::create(['name' => 'Applicant', 'status' => 'Pending']);
        $stall = Stall::create([
            'stall_number' => 'F-01', 'market_section' => 'Food',
            'status' => 'Available', 'monthly_rate' => 3500,
        ]);

        $this->actingAs($admin)->from(route('rentals'))->post(route('rentals.store'), [
            'vendor_id' => $vendor->id, 'stall_id' => $stall->id,
            'contract_number' => 'CTR-MODAL', 'start_date' => '2026-10-04',
            'end_date' => '2027-10-04', 'rent_amount' => 3500,
            'billing_cycle' => 'Monthly', 'status' => 'Active',
        ])->assertSessionHasNoErrors()->assertRedirect(route('rentals'));

        $this->assertDatabaseHas('rentals', ['contract_number' => 'CTR-MODAL', 'vendor_id' => $vendor->id]);
        $this->assertDatabaseHas('stalls', ['id' => $stall->id, 'status' => 'Occupied']);
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'stall_number' => 'F-01', 'status' => 'Active']);
        $this->actingAs($admin)->get(route('rentals'))
            ->assertViewHas('availableStalls', fn ($stalls): bool => $stalls->isEmpty())
            ->assertViewHas('vendors', fn ($vendors): bool => $vendors->isEmpty());
    }

    public function test_rental_modal_reopens_with_values_after_validation_failure(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vendor = Vendor::create(['name' => 'Applicant', 'status' => 'Pending']);
        $stall = Stall::create(['stall_number' => 'F-02', 'market_section' => 'Food', 'status' => 'Available']);

        $this->actingAs($admin)->from(route('rentals'))->post(route('rentals.store'), [
            'vendor_id' => $vendor->id, 'stall_id' => $stall->id,
            'contract_number' => 'CTR-RETRY', 'start_date' => '2026-10-04',
            'end_date' => '2026-09-04', 'rent_amount' => 3500,
            'billing_cycle' => 'Quarterly', 'status' => 'Pending',
        ])->assertSessionHasErrors('end_date');

        $this->withCookie(config('session.cookie'), session()->getId())
            ->get(route('rentals'))
            ->assertSee('Please correct the following:')
            ->assertSee('value="CTR-RETRY"', false)
            ->assertSee('id="addRentalModal" class="app-modal-overlay "', false);
        $this->assertDatabaseMissing('rentals', ['contract_number' => 'CTR-RETRY']);
    }

    public function test_admin_cannot_assign_an_occupied_stall_to_another_vendor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $stall = Stall::create([
            'stall_number' => 'A-01',
            'market_section' => 'Fresh Produce',
            'status' => 'Occupied',
        ]);
        $currentVendor = Vendor::create(['name' => 'Current Vendor', 'status' => 'Active']);
        $newVendor = Vendor::create(['name' => 'New Vendor', 'status' => 'Inactive']);
        Rental::create([
            'vendor_id' => $currentVendor->id,
            'stall_id' => $stall->id,
            'contract_number' => 'CTR-001',
            'start_date' => today(),
            'end_date' => today()->addYear(),
            'rent_amount' => 3000,
            'billing_cycle' => 'Monthly',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($admin)->post(route('rentals.store'), [
            'vendor_id' => $newVendor->id,
            'stall_id' => $stall->id,
            'contract_number' => 'CTR-002',
            'start_date' => today()->toDateString(),
            'end_date' => today()->addYear()->toDateString(),
            'rent_amount' => 3000,
            'billing_cycle' => 'Monthly',
            'status' => 'Active',
        ]);

        $response->assertSessionHasErrors('stall_id');
        $this->assertDatabaseMissing('rentals', ['contract_number' => 'CTR-002']);
    }

    public function test_terminating_a_rental_releases_the_stall_and_vendor_assignment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $stall = Stall::create([
            'stall_number' => 'B-01',
            'market_section' => 'Dry Goods',
            'status' => 'Occupied',
        ]);
        $vendor = Vendor::create([
            'name' => 'Vendor One',
            'stall_number' => 'B-01',
            'status' => 'Active',
        ]);
        $rental = Rental::create([
            'vendor_id' => $vendor->id,
            'stall_id' => $stall->id,
            'contract_number' => 'CTR-003',
            'start_date' => today(),
            'end_date' => today()->addYear(),
            'rent_amount' => 3000,
            'billing_cycle' => 'Monthly',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($admin)->put(route('rentals.update', $rental), [
            'vendor_id' => $vendor->id,
            'stall_id' => $stall->id,
            'contract_number' => 'CTR-003',
            'start_date' => today()->toDateString(),
            'end_date' => today()->addYear()->toDateString(),
            'rent_amount' => 3000,
            'billing_cycle' => 'Monthly',
            'status' => 'Terminated',
        ]);

        $response->assertRedirect(route('rentals'));
        $this->assertDatabaseHas('rentals', ['id' => $rental->id, 'status' => 'Terminated']);
        $this->assertDatabaseHas('stalls', ['id' => $stall->id, 'status' => 'Available']);
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => 'Inactive', 'stall_number' => null]);
    }

    public function test_admin_can_view_due_dates_and_reports_workspaces(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('due-dates'))->assertOk()->assertSee('Due Dates');
        $this->actingAs($admin)->get(route('reports'))->assertOk()->assertSee('Reports');
    }

    public function test_creating_an_active_vendor_creates_a_rental_and_activity_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Stall::create([
            'stall_number' => 'C-01',
            'market_section' => 'General Merchandise',
            'status' => 'Available',
        ]);

        $response = $this->actingAs($admin)->post(route('vendors.store'), [
            'name' => 'New Vendor',
            'stall_number' => 'C-01',
            'contact_number' => '+63 917 555 0000',
            'email' => 'new-vendor@example.com',
            'market_section' => 'General Merchandise',
            'monthly_rent' => 3500,
            'billing_cycle' => 'Monthly',
            'contract_start_date' => today()->toDateString(),
            'contract_end_date' => today()->addYear()->toDateString(),
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('vendors.index'));
        $vendor = Vendor::where('email', 'new-vendor@example.com')->firstOrFail();
        $this->assertDatabaseHas('rentals', ['vendor_id' => $vendor->id, 'status' => 'Active']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'created', 'subject_id' => $vendor->id]);
    }
}
