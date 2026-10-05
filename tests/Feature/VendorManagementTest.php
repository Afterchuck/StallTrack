<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Rental;
use App\Models\Stall;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_a_vendor_payment_and_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Stall::create(['stall_number' => 'A-01', 'market_section' => 'Fresh Produce', 'status' => 'Available']);
        Stall::create(['stall_number' => 'B-05', 'market_section' => 'Dry Goods', 'status' => 'Available']);
        $vendor = Vendor::create([
            'name' => 'Vendor One',
            'email' => 'vendor@example.com',
            'stall_number' => 'A-01',
            'market_section' => 'Fresh Produce',
            'monthly_rent' => 1200,
            'billing_cycle' => 'Monthly',
            'contract_start_date' => today(),
            'contract_end_date' => today()->addYear(),
            'status' => 'Active',
        ]);

        $this->actingAs($admin)->get(route('vendors.show', $vendor))
            ->assertOk()
            ->assertSee('Vendor One');

        $this->actingAs($admin)->post(route('vendors.payments.store', $vendor), [
            'amount' => 1200,
            'paid_at' => today()->toDateString(),
            'receipt_number' => 'OR-100001',
        ])->assertRedirect(route('vendors.show', $vendor));

        $this->assertDatabaseHas('payments', [
            'vendor_name' => 'Vendor One',
            'receipt_number' => 'OR-100001',
            'status' => 'Recorded',
        ]);

        $payment = Payment::where('receipt_number', 'OR-100001')->firstOrFail();

        $this->actingAs($admin)->patch(route('vendors.payments.paid', [$vendor, $payment]))
            ->assertRedirect(route('vendors.show', $vendor));

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'Paid',
        ]);

        $this->actingAs($admin)->put(route('vendors.update', $vendor), [
            'name' => 'Vendor Updated',
            'email' => 'vendor@example.com',
            'stall_number' => 'B-05',
            'market_section' => 'Dry Goods',
            'monthly_rent' => 1500,
            'billing_cycle' => 'Monthly',
            'contract_start_date' => today()->toDateString(),
            'contract_end_date' => today()->addYear()->toDateString(),
            'status' => 'Active',
        ])->assertRedirect(route('vendors.show', $vendor));

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'name' => 'Vendor Updated', 'stall_number' => 'B-05']);
        $this->assertDatabaseHas('payments', ['vendor_name' => 'Vendor Updated', 'receipt_number' => 'OR-100001']);
    }

    public function test_admin_can_open_stall_details_and_use_matching_details_and_edit_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $stall = Stall::create([
            'stall_number' => 'C-12',
            'market_section' => 'Dry Goods',
            'location' => 'Building C',
            'stall_type' => 'Standard',
            'dimensions' => '3m x 3m',
            'monthly_rate' => 2500,
            'status' => 'Available',
        ]);

        $this->actingAs($admin)
            ->get(route('stalls'))
            ->assertSee(route('stalls.show', $stall))
            ->assertSee('Rate per square meter')
            ->assertSee('Length × Width')
            ->assertSee('stall_rate_per_sqm_input')
            ->assertSee('Edit stall C-12');

        $this->get(route('stalls.show', $stall))
            ->assertOk()
            ->assertSee('Stall C-12')
            ->assertSee('Building C')
            ->assertSee('Dry Goods');

        $this->get(route('vendors.index'))
            ->assertSee('Rate per square meter')
            ->assertSee('stall_rate_per_sqm_input')
            ->assertSee('stall_rate_formula');
    }

    public function test_admin_can_delete_a_vendor_without_rental_or_payment_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vendor = Vendor::create(['name' => 'Unlinked Vendor', 'status' => 'Inactive']);

        $this->actingAs($admin)->delete(route('vendors.destroy', $vendor))
            ->assertRedirect(route('vendors.index'))
            ->assertSessionHas('success', 'Vendor account deleted.');

        $this->assertDatabaseMissing('vendors', ['id' => $vendor->id]);
    }

    public function test_admin_cannot_delete_a_vendor_with_direct_payment_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vendor = Vendor::create(['name' => 'Paid Vendor', 'status' => 'Inactive']);
        Payment::create([
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->name,
            'receipt_number' => 'OR-DELETE-001',
            'paid_at' => today(),
            'amount' => 100,
            'status' => 'Paid',
        ]);

        $this->actingAs($admin)->from(route('vendors.index'))->delete(route('vendors.destroy', $vendor))
            ->assertSessionHasErrors([
                'vendor' => 'This vendor has rental or payment history and cannot be deleted. Set the vendor to inactive instead.',
            ]);

        $this->assertModelExists($vendor);
        $this->assertDatabaseHas('payments', ['receipt_number' => 'OR-DELETE-001', 'vendor_id' => $vendor->id]);
    }

    public function test_admin_cannot_delete_a_vendor_with_rental_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vendor = Vendor::create(['name' => 'Former Tenant', 'status' => 'Inactive']);
        $stall = Stall::create(['stall_number' => 'H-01', 'market_section' => 'Dry Goods', 'status' => 'Available']);
        $rental = Rental::create([
            'vendor_id' => $vendor->id,
            'stall_id' => $stall->id,
            'contract_number' => 'CTR-H01',
            'start_date' => today()->subYear(),
            'end_date' => today()->subDay(),
            'rent_amount' => 1000,
            'billing_cycle' => 'Monthly',
            'status' => 'Expired',
        ]);

        $this->actingAs($admin)->from(route('vendors.index'))->delete(route('vendors.destroy', $vendor))
            ->assertSessionHasErrors([
                'vendor' => 'This vendor has rental or payment history and cannot be deleted. Set the vendor to inactive instead.',
            ]);

        $this->assertModelExists($vendor);
        $this->assertModelExists($rental);
    }
}
