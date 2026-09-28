<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_registration_creates_an_admin_account_and_redirects_to_the_admin_dashboard(): void
    {
        $response = $this->post(route('register.store'), $this->registrationData());

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHasNoErrors();
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Maria Clara Del Rosario',
            'email' => 'admin@example.com',
            'mobile_number' => '+639171234567',
            'role' => 'admin',
        ]);
    }

    public function test_vendor_registration_creates_a_vendor_account_and_redirects_to_the_vendor_dashboard(): void
    {
        $response = $this->post(route('register.store'), $this->registrationData([
            'email' => 'vendor@example.com',
            'role' => 'vendor',
        ]));

        $response->assertRedirect(route('vendor.dashboard'));
        $response->assertSessionHasNoErrors();
        $this->assertAuthenticated();
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
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
        ], $overrides);
    }
}
