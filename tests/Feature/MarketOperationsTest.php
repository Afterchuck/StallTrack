<?php

namespace Tests\Feature;

use App\Models\Bill;
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
            'contract_number' => 'MANUAL-CONTRACT-SHOULD-BE-IGNORED',
            'start_date' => '2026-10-04',
            'end_date' => '2027-10-04', 'rent_amount' => 3500,
            'billing_cycle' => 'Monthly', 'status' => 'Active',
        ])->assertSessionHasNoErrors()->assertRedirect(route('rentals'));

        $rental = Rental::where('vendor_id', $vendor->id)->firstOrFail();
        $this->assertMatchesRegularExpression('/^CTR-[A-Z0-9]{8}$/', $rental->contract_number);
        $this->assertDatabaseHas('rentals', ['contract_number' => $rental->contract_number, 'vendor_id' => $vendor->id]);
        $this->assertDatabaseHas('stalls', ['id' => $stall->id, 'status' => 'Occupied']);
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'stall_number' => 'F-01', 'status' => 'Active']);
        $this->actingAs($admin)->get(route('rentals'))
            ->assertSee('value="Auto-generated on save" readonly', false)
            ->assertDontSee('name="contract_number"', false)
            ->assertViewHas('availableStalls', fn ($stalls): bool => $stalls->isEmpty())
            ->assertViewHas('vendors', fn ($vendors): bool => $vendors->isEmpty());
    }

    public function test_stall_numbers_are_generated_and_skip_existing_numbers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $attributes = [
            'market_section' => 'Food', 'length_m' => 3, 'width_m' => 2,
            'rate_per_sqm' => 100, 'status' => 'Available',
        ];

        $this->actingAs($admin)->post(route('stalls.store'), $attributes)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Stall ST-000001 registered successfully.');

        Stall::factory()->create(['stall_number' => 'ST-000003']);

        $this->post(route('stalls.store'), $attributes)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Stall ST-000004 registered successfully.');

        $this->post(route('stalls.store'), $attributes)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Stall ST-000005 registered successfully.');

        $this->assertDatabaseCount('stalls', 4);
        $this->assertDatabaseHas('stalls', ['stall_number' => 'ST-000001', 'monthly_rate' => 600]);
        $this->assertDatabaseHas('stalls', ['stall_number' => 'ST-000004', 'monthly_rate' => 600]);
        $this->assertDatabaseHas('stalls', ['stall_number' => 'ST-000005', 'monthly_rate' => 600]);

        $this->post(route('stalls.store'), ['stall_number' => 'ST-000001'] + $attributes)
            ->assertSessionHasErrors('stall_number');
        $this->assertDatabaseCount('stalls', 4);

        $this->get(route('stalls'))->assertSee('value="Auto-generated on save" readonly', false);
        $this->get(route('vendors.index'))
            ->assertDontSee('value="Auto-generated on save" readonly', false)
            ->assertDontSee('addStallModal', false);
    }

    public function test_admin_creates_a_stall_with_monthly_rate_calculated_from_area_and_sqm_rate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('stalls'))
            ->assertSee('name="rate_per_sqm" id="stall_rate_per_sqm_input"', false)
            ->assertSee('id="stall_rate_input" class="pl-8" readonly', false);

        $this->post(route('stalls.store'), [
            'stall_number' => 'F-10',
            'market_section' => 'Food Court',
            'length_m' => '3.00',
            'width_m' => '2.50',
            'rate_per_sqm' => '125.00',
            'monthly_rate' => '1.00',
            'status' => 'Available',
        ])->assertSessionHasNoErrors()->assertRedirect(route('stalls'));

        $this->assertDatabaseHas('stalls', [
            'stall_number' => 'F-10',
            'length_m' => '3.00',
            'width_m' => '2.50',
            'rate_per_sqm' => '125.00',
            'dimensions' => '3.00m x 2.50m',
            'monthly_rate' => '937.50',
        ]);
    }

    public function test_admin_updates_a_stall_and_recalculates_monthly_rate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $stall = Stall::create([
            'stall_number' => 'F-11',
            'market_section' => 'Food Court',
            'status' => 'Available',
            'length_m' => 2,
            'width_m' => 2,
            'rate_per_sqm' => 100,
            'monthly_rate' => 400,
        ]);

        $this->actingAs($admin)->put(route('stalls.update', $stall), [
            'stall_number' => 'F-11',
            'market_section' => 'Food Court',
            'length_m' => '2.50',
            'width_m' => '4.00',
            'rate_per_sqm' => '200.00',
            'status' => 'Available',
        ])->assertRedirect(route('stalls'));

        $this->assertDatabaseHas('stalls', [
            'id' => $stall->id,
            'dimensions' => '2.50m x 4.00m',
            'monthly_rate' => '2000.00',
        ]);
    }

    public function test_admin_cannot_save_a_stall_when_the_calculated_rate_exceeds_the_supported_amount(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->from(route('stalls'))->post(route('stalls.store'), [
            'stall_number' => 'F-12',
            'market_section' => 'Food Court',
            'length_m' => '999999.99',
            'width_m' => '999999.99',
            'rate_per_sqm' => '99999999.99',
            'status' => 'Available',
        ])->assertSessionHasErrors('rate_per_sqm');

        $this->assertDatabaseMissing('stalls', ['stall_number' => 'F-12']);
    }

    public function test_rental_modal_reopens_with_values_after_validation_failure(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vendor = Vendor::create(['name' => 'Applicant', 'status' => 'Pending']);
        $stall = Stall::create(['stall_number' => 'F-02', 'market_section' => 'Food', 'status' => 'Available']);

        $this->actingAs($admin)->from(route('rentals'))->post(route('rentals.store'), [
            'vendor_id' => $vendor->id, 'stall_id' => $stall->id,
            'start_date' => '2026-10-04',
            'end_date' => '2026-09-04', 'rent_amount' => 3500,
            'billing_cycle' => 'Quarterly', 'status' => 'Pending',
        ])->assertSessionHasErrors('end_date');

        $this->withCookie(config('session.cookie'), session()->getId())
            ->get(route('rentals'))
            ->assertSee('Please correct the following:')
            ->assertSee('value="Auto-generated on save" readonly', false)
            ->assertSee('id="addRentalModal" class="app-modal-overlay "', false);
        $this->assertDatabaseCount('rentals', 0);
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

    public function test_admin_can_set_a_rental_inactive_from_the_edit_modal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vendor = Vendor::create(['name' => 'Active Vendor', 'stall_number' => 'D-05', 'status' => 'Active']);
        $stall = Stall::create(['stall_number' => 'D-05', 'market_section' => 'Dry Goods', 'status' => 'Occupied']);
        $rental = Rental::create([
            'vendor_id' => $vendor->id,
            'stall_id' => $stall->id,
            'contract_number' => 'CTR-D05',
            'start_date' => today(),
            'end_date' => today()->addYear(),
            'rent_amount' => 1000,
            'billing_cycle' => 'Monthly',
            'status' => 'Active',
        ]);

        $this->actingAs($admin)->put(route('rentals.update', $rental), [
            'vendor_id' => $vendor->id,
            'stall_id' => $stall->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addYear()->toDateString(),
            'rent_amount' => 1000,
            'billing_cycle' => 'Monthly',
            'status' => 'Inactive',
        ])->assertRedirect(route('rentals'));

        $this->assertDatabaseHas('rentals', ['id' => $rental->id, 'status' => 'Inactive']);
        $this->assertDatabaseHas('stalls', ['id' => $stall->id, 'status' => 'Available']);
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => 'Inactive', 'stall_number' => null]);
    }

    public function test_admin_can_delete_a_stall_without_rental_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $stall = Stall::create(['stall_number' => 'D-01', 'market_section' => 'Dry Goods', 'status' => 'Available']);

        $this->actingAs($admin)->get(route('stalls'))
            ->assertSee('id="deleteStallModal"', false)
            ->assertSee('Delete stall?', false)
            ->assertSee('data-stall-number="D-01"', false)
            ->assertDontSee('onsubmit="return confirm(', false);

        $this->delete(route('stalls.destroy', $stall))
            ->assertRedirect(route('stalls'))
            ->assertSessionHas('success', 'Stall D-01 deleted successfully.');

        $this->assertDatabaseMissing('stalls', ['id' => $stall->id]);
    }

    public function test_admin_cannot_delete_a_stall_with_rental_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vendor = Vendor::create(['name' => 'History Vendor', 'status' => 'Inactive']);
        $stall = Stall::create(['stall_number' => 'D-02', 'market_section' => 'Dry Goods', 'status' => 'Available']);
        Rental::create([
            'vendor_id' => $vendor->id,
            'stall_id' => $stall->id,
            'contract_number' => 'CTR-D02',
            'start_date' => today()->subYear(),
            'end_date' => today()->subDay(),
            'rent_amount' => 1000,
            'billing_cycle' => 'Monthly',
            'status' => 'Expired',
        ]);

        $this->followingRedirects()->actingAs($admin)->from(route('stalls'))
            ->delete(route('stalls.destroy', $stall))
            ->assertSee('This stall has rental history and cannot be deleted. Set it to inactive instead.');

        $this->assertModelExists($stall);
    }

    public function test_deleting_an_active_rental_releases_the_stall_and_clears_vendor_assignment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vendor = Vendor::create(['name' => 'Active Vendor', 'stall_number' => 'D-03', 'status' => 'Active']);
        $stall = Stall::create(['stall_number' => 'D-03', 'market_section' => 'Dry Goods', 'status' => 'Occupied']);
        $rental = Rental::create([
            'vendor_id' => $vendor->id,
            'stall_id' => $stall->id,
            'contract_number' => 'CTR-D03',
            'start_date' => today(),
            'end_date' => today()->addYear(),
            'rent_amount' => 1000,
            'billing_cycle' => 'Monthly',
            'status' => 'Active',
        ]);

        $this->actingAs($admin)->get(route('rentals'))
            ->assertSee('id="editRentalModal"', false)
            ->assertSee('id="editRentalForm"', false)
            ->assertSee('id="edit_rental_status"', false)
            ->assertSee('data-update-url-template="'.route('rentals.update', ['rental' => '__rental__']).'"', false)
            ->assertSee('id="deleteRentalModal"', false)
            ->assertSee('data-contract-number="CTR-D03"', false)
            ->assertDontSee('onsubmit="return confirm(', false);

        $this->delete(route('rentals.destroy', $rental))
            ->assertRedirect(route('rentals'))
            ->assertSessionHas('success', 'Rental contract CTR-D03 deleted successfully.');

        $this->assertDatabaseMissing('rentals', ['id' => $rental->id]);
        $this->assertDatabaseHas('stalls', ['id' => $stall->id, 'status' => 'Available']);
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => 'Inactive', 'stall_number' => null]);
    }

    public function test_admin_cannot_delete_a_rental_with_billing_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vendor = Vendor::create(['name' => 'Billed Vendor', 'status' => 'Active']);
        $stall = Stall::create(['stall_number' => 'D-04', 'market_section' => 'Dry Goods', 'status' => 'Occupied']);
        $rental = Rental::create([
            'vendor_id' => $vendor->id,
            'stall_id' => $stall->id,
            'contract_number' => 'CTR-D04',
            'start_date' => today(),
            'end_date' => today()->addYear(),
            'rent_amount' => 1000,
            'billing_cycle' => 'Monthly',
            'status' => 'Active',
        ]);
        Bill::factory()->for($vendor)->create(['rental_id' => $rental->id]);

        $this->actingAs($admin)->from(route('rentals'))->delete(route('rentals.destroy', $rental))
            ->assertSessionHas('error', 'This rental has billing history and cannot be deleted. Update its status to Terminated instead.');

        $this->assertModelExists($rental);
        $this->assertDatabaseHas('stalls', ['id' => $stall->id, 'status' => 'Occupied']);
        $this->get(route('rentals'))
            ->assertSee('This rental has billing history and cannot be deleted. Update its status to Terminated instead.');
    }

    public function test_admin_can_view_due_dates_and_reports_workspaces(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('due-dates'))->assertOk()->assertSee('Due Dates');
        $this->actingAs($admin)->get(route('reports'))->assertOk()->assertSee('Reports');
    }

    public function test_add_vendor_ignores_stall_and_financial_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $stall = Stall::factory()->create(['status' => 'Available']);

        $this->actingAs($admin)->post(route('vendors.store'), [
            'name' => 'Details Only Vendor',
            'market_section' => 'Food',
            'stall_number' => $stall->stall_number,
            'monthly_rent' => 3500,
            'billing_cycle' => 'Weekly',
            'contract_start_date' => '2026-10-07',
            'contract_end_date' => '2027-10-07',
            'status' => 'Active',
        ])->assertSessionHasNoErrors()->assertRedirect(route('vendors.index'));

        $this->assertDatabaseHas('vendors', [
            'name' => 'Details Only Vendor', 'stall_number' => null,
            'monthly_rent' => null, 'contract_start_date' => null,
            'contract_end_date' => null, 'contract_until' => null, 'status' => 'Pending',
        ]);
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseHas('stalls', ['id' => $stall->id, 'status' => 'Available']);
    }

    public function test_vendor_management_page_hides_vendor_and_stall_creation_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('vendors.index'))
            ->assertDontSee('+ Add Vendor')
            ->assertDontSee('+ Add Stall')
            ->assertDontSee('id="addVendorModal"', false)
            ->assertDontSee('id="addVendorForm"', false)
            ->assertDontSee('id="addStallModal"', false)
            ->assertDontSee('id="addStallForm"', false)
            ->assertDontSee('id="confirmStallModal"', false)
            ->assertSee('id="editVendorModal"', false)
            ->assertSee('id="deleteVendorModal"', false);
    }

    public function test_admin_can_add_vendor_details_then_assign_a_stall_through_add_rental(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $stall = Stall::factory()->create([
            'stall_number' => 'C-01',
            'market_section' => 'General Merchandise',
            'status' => 'Available',
        ]);

        $response = $this->actingAs($admin)->post(route('vendors.store'), [
            'name' => 'New Vendor',
            'contact_number' => '+63 917 555 0000',
            'email' => 'new-vendor@example.com',
            'residential_address' => '123 Market Street',
            'market_section' => 'General Merchandise',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('vendors.index'));
        $vendor = Vendor::where('email', 'new-vendor@example.com')->firstOrFail();
        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'name' => 'New Vendor',
            'residential_address' => '123 Market Street',
            'stall_number' => null,
            'monthly_rent' => null,
            'contract_start_date' => null,
            'contract_end_date' => null,
            'status' => 'Pending',
        ]);
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseHas('stalls', ['id' => $stall->id, 'status' => 'Available']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'created', 'subject_id' => $vendor->id]);

        $this->get(route('rentals'))
            ->assertSee('New Vendor')
            ->assertViewHas('vendors', fn ($vendors): bool => $vendors->contains($vendor));

        $this->post(route('rentals.store'), [
            'vendor_id' => $vendor->id,
            'stall_id' => $stall->id,
            'contract_number' => 'CTR-NEW-VENDOR',
            'start_date' => today()->toDateString(),
            'end_date' => today()->addYear()->toDateString(),
            'rent_amount' => 3500,
            'billing_cycle' => 'Bi-weekly',
            'status' => 'Active',
        ])->assertSessionHasNoErrors()->assertRedirect(route('rentals'));

        $this->assertDatabaseHas('rentals', ['vendor_id' => $vendor->id, 'status' => 'Active']);
        $this->assertDatabaseHas('stalls', ['stall_number' => 'C-01', 'status' => 'Occupied']);
        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id, 'stall_number' => 'C-01', 'monthly_rent' => 3500,
            'billing_cycle' => 'Bi-weekly', 'status' => 'Active',
        ]);
    }
}
