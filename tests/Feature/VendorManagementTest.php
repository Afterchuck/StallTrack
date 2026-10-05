<?php

namespace Tests\Feature;

use App\Models\Payment;
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
}
