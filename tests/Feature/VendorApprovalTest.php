<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_vendor_account_is_created_pending_and_not_authenticated(): void
    {
        $response = $this->post(route('register.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Vendor',
            'email' => 'maria@example.com',
            'mobile_number' => '9171234567',
            'password' => 'A-long-test-passphrase-123',
            'password_confirmation' => 'A-long-test-passphrase-123',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Your account has been created and is awaiting admin approval.');
        $this->assertGuest();
        $this->assertDatabaseHas('vendors', [
            'email' => 'maria@example.com',
            'approval_status' => 'Pending',
        ]);
    }

    public function test_pending_vendor_cannot_log_in_until_approved(): void
    {
        $user = User::factory()->create([
            'email' => 'pending@example.com',
            'password' => 'A-long-test-passphrase-123',
            'role' => 'vendor',
        ]);
        Vendor::factory()->for($user)->create(['approval_status' => 'Pending']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'A-long-test-passphrase-123',
        ])->assertSessionHasErrors([
            'email' => 'Your vendor account has not been approved yet.',
        ]);

        $this->assertGuest();
    }

    public function test_admin_can_approve_a_vendor_account_and_vendor_can_then_log_in(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'email' => 'approved@example.com',
            'password' => 'A-long-test-passphrase-123',
            'role' => 'vendor',
        ]);
        $vendor = Vendor::factory()->for($user)->create(['approval_status' => 'Pending']);

        $this->actingAs($admin)
            ->patch(route('vendors.approval.update', $vendor), ['approval_status' => 'Approved'])
            ->assertRedirect(route('vendors.index'));
        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'approval_status' => 'Approved',
        ]);

        $this->post(route('logout'));
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'A-long-test-passphrase-123',
        ])->assertRedirect(route('vendor.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_not_approved_vendor_is_logged_out_of_vendor_pages(): void
    {
        $user = User::factory()->create([
            'email' => 'rejected@example.com',
            'password' => 'A-long-test-passphrase-123',
            'role' => 'vendor',
        ]);
        Vendor::factory()->for($user)->create(['approval_status' => 'Not approved']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'A-long-test-passphrase-123',
        ])->assertSessionHasErrors([
            'email' => 'Your vendor account has not been approved yet.',
        ]);
        $this->assertGuest();

        $this->actingAs($user)
            ->get(route('vendor.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_non_admin_cannot_change_vendor_approval_status(): void
    {
        $user = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::factory()->for($user)->create(['approval_status' => 'Pending']);

        $this->actingAs($user)
            ->patch(route('vendors.approval.update', $vendor), ['approval_status' => 'Approved'])
            ->assertForbidden();

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'approval_status' => 'Pending',
        ]);
    }

    public function test_admin_vendor_list_has_account_approval_control_and_details_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::factory()->for($user)->create([
            'name' => 'Approval Candidate',
            'approval_status' => 'Pending',
        ]);

        $this->actingAs($admin)
            ->get(route('vendors.index'))
            ->assertSee('Not approved')
            ->assertSee(route('vendors.approval.update', $vendor))
            ->assertSee(route('vendors.show', $vendor))
            ->assertSee(route('vendors.edit', $vendor))
            ->assertDontSee('Edit details');
    }
}
