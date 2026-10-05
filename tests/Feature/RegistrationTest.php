<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_cannot_access_the_admin_dashboard(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)->get(route('dashboard'))->assertForbidden();
    }

    public function test_public_registration_creates_a_vendor_account_and_redirects_to_login_pending_approval(): void
    {
        $response = $this->post(route('register.store'), $this->registrationData());

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Your account has been created and is awaiting admin approval.');
        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'name' => 'Maria Clara Del Rosario',
            'email' => 'admin@example.com',
            'mobile_number' => '+639171234567',
            'role' => 'vendor',
        ]);
    }

    public function test_vendor_registration_cannot_claim_an_admin_role_and_redirects_to_login_pending_approval(): void
    {
        $response = $this->post(route('register.store'), $this->registrationData([
            'email' => 'vendor@example.com',
            'role' => 'admin',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasNoErrors();
        $this->assertGuest();
        $this->assertDatabaseHas('users', ['email' => 'vendor@example.com', 'role' => 'vendor']);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Maria Clara',
            'last_name' => 'Del Rosario',
            'email' => 'admin@example.com',
            'mobile_number' => '9171234567',
            'role' => 'admin',
            'password' => 'A-long-test-passphrase-123',
            'password_confirmation' => 'A-long-test-passphrase-123',
            'terms' => '1',
        ], $overrides);
    }
}
