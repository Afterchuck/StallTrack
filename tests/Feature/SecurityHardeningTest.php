<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Stall;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_without_disclosing_account_existence(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), ['email' => 'missing@example.com', 'password' => 'wrong'])
                ->assertSessionHasErrors(['email' => 'Those credentials do not match our records.']);
        }

        $this->post(route('login.store'), ['email' => 'missing@example.com', 'password' => 'wrong'])
            ->assertTooManyRequests()->assertHeader('Retry-After');
        $this->assertGuest();
    }

    public function test_registration_can_be_disabled_and_is_rate_limited_when_enabled(): void
    {
        config(['security.registration_enabled' => false]);
        $this->get(route('register'))->assertNotFound();
        $this->post(route('register.store'), $this->registrationData())->assertNotFound();
        $this->assertDatabaseCount('users', 0);
        config(['security.registration_enabled' => true]);
        $this->post(route('register.store'), [])->assertSessionHasErrors();
        $this->post(route('register.store'), [])->assertTooManyRequests();
        $this->assertDatabaseCount('vendors', 0);
    }

    public function test_registration_creates_linked_vendor_and_ignores_a_forged_admin_role(): void
    {
        config(['security.registration_enabled' => true]);
        $this->post(route('register.store'), $this->registrationData())->assertSessionHasNoErrors();
        $this->post(route('logout'));
        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'role' => 'vendor']);
        $this->assertSame(User::firstOrFail()->id, Vendor::firstOrFail()->user_id);
    }

    public function test_short_password_creates_neither_user_nor_vendor(): void
    {
        config(['security.registration_enabled' => true]);

        $this->post(route('register.store'), array_replace($this->registrationData(), ['password' => 'short', 'password_confirmation' => 'short']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('vendors', 0);
    }

    public function test_registration_cannot_claim_an_existing_unlinked_vendor_email(): void
    {
        config(['security.registration_enabled' => true]);
        Vendor::factory()->create(['email' => 'new@example.com', 'user_id' => null]);

        $this->post(route('register.store'), $this->registrationData())->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('vendors', 1);
    }

    public function test_email_match_alone_does_not_expose_vendor_bills(): void
    {
        $user = User::factory()->create(['role' => 'vendor', 'email' => 'same@example.com']);
        $vendor = Vendor::factory()->create(['email' => $user->email, 'user_id' => null]);
        $bill = Bill::factory()->for($vendor)->create(['vendor_name' => 'PRIVATE VENDOR', 'amount' => '9876.54']);

        $this->actingAs($user)->get(route('vendor.dashboard'))->assertDontSee('9,876.54');
        $this->get(route('vendor.bills.show', $bill))->assertNotFound();
        $this->get(route('vendor.payments'))->assertViewHas('vendor', null);
    }

    public function test_deleting_unlinked_vendor_does_not_delete_a_same_email_account(): void
    {
        $user = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::factory()->create(['email' => $user->email, 'user_id' => null]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->delete(route('vendors.destroy', $vendor))->assertRedirect();

        $this->assertModelExists($user);
        $this->assertModelMissing($vendor);
    }

    public function test_contact_email_updates_do_not_change_login_identity(): void
    {
        $user = User::factory()->create(['role' => 'vendor', 'email' => 'original@example.com']);
        $vendor = Vendor::factory()->for($user)->create(['email' => $user->email]);
        $stall = Stall::factory()->create(['status' => 'Available']);
        $data = $this->vendorData($stall) + ['user_id' => 9999];
        $data['email'] = 'new-contact@example.com';

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put(route('vendors.update', $vendor), $data)->assertSessionHasNoErrors();

        $this->assertSame('original@example.com', $user->fresh()->email);
        $this->assertSame($user->id, $vendor->fresh()->user_id);
    }

    public function test_vendor_id_photo_is_stored_privately_and_svg_is_rejected(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $stall = Stall::factory()->create(['status' => 'Available']);
        $photo = UploadedFile::fake()->createWithContent('id.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j4K0AAAAASUVORK5CYII='));
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post(route('vendors.store'), $this->vendorData($stall) + ['photo' => $photo])->assertSessionHasNoErrors();

        $vendor = Vendor::latest('id')->firstOrFail();
        Storage::disk('local')->assertExists($vendor->photo_path);
        Storage::disk('public')->assertMissing($vendor->photo_path);
        $this->post(route('vendors.store'), $this->vendorData($stall) + [
            'photo' => UploadedFile::fake()->createWithContent('id.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
        ])->assertSessionHasErrors('photo');
        $this->assertDatabaseCount('vendors', 1);
    }

    public function test_security_headers_and_no_store_are_present(): void
    {
        $this->get(route('login'))->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('payments'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_state_changes_require_csrf_outside_the_test_environment(): void
    {
        $this->app['env'] = 'local';

        $this->post(route('login.store'), ['email' => 'missing@example.com', 'password' => 'anything'])
            ->assertStatus(419);
    }

    public function test_demo_seeding_is_blocked_in_production_before_any_data_changes(): void
    {
        $this->app['env'] = 'production';
        try {
            (new DatabaseSeeder)->run();
            $this->fail('Production seeding should be refused.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('disabled', $exception->getMessage());
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('vendors', 0);
    }

    public function test_admin_seeder_does_not_reset_an_existing_password(): void
    {
        $this->app['env'] = 'local';
        $admin = User::factory()->create(['email' => 'admin@stalltrack.com', 'role' => 'admin']);
        $originalHash = $admin->password;

        (new AdminSeeder)->run();

        $this->assertSame($originalHash, $admin->fresh()->password);
    }

    public function test_admin_seeder_promotes_an_existing_account_without_resetting_password(): void
    {
        $this->app['env'] = 'local';
        config(['security.demo_admin_password' => 'A-unique-long-passphrase-123']);
        $admin = User::factory()->create([
            'email' => 'admin@stalltrack.com',
            'role' => 'vendor',
            'password' => Hash::make('already-set-password'),
        ]);

        (new AdminSeeder)->run();

        $this->assertTrue(Hash::check('already-set-password', $admin->fresh()->password));
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_deployment_check_flags_known_demo_admin_password_without_exposing_it(): void
    {
        Storage::fake('public');
        User::factory()->create(['role' => 'admin', 'password' => Hash::make('admin123')]);

        $this->artisan('security:check')->expectsOutputToContain('known demo password')
            ->assertFailed();
    }

    public function test_legacy_payment_routes_reject_invalid_amounts_and_future_dates(): void
    {
        $vendor = Vendor::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach ([route('payments.store'), route('vendors.payments.store', $vendor)] as $url) {
            $this->post($url, ['vendor_id' => $vendor->id, 'amount' => '0.001', 'paid_at' => today()->addDay()->toDateString(), 'receipt_number' => 'INVALID'])
                ->assertSessionHasErrors(['amount', 'paid_at']);
        }
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_production_https_responses_have_hsts(): void
    {
        $this->app['env'] = 'production';

        $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_admin_seeder_uses_the_default_demo_password_when_config_is_blank(): void
    {
        $this->app['env'] = 'local';
        config(['security.demo_admin_password' => null]);

        (new AdminSeeder)->run();

        $this->assertDatabaseHas('users', ['email' => 'admin@stalltrack.com', 'role' => 'admin']);
        $this->assertTrue(Hash::check('admin123', User::firstOrFail()->password));
    }

    public function test_default_demo_admin_can_log_in_with_the_expected_credentials(): void
    {
        $this->assertTrue(app()->environment('testing'));
        config(['security.demo_admin_password' => 'admin123']);

        (new AdminSeeder)->run();

        $this->post(route('login.store'), [
            'email' => 'admin@stalltrack.com',
            'password' => 'admin123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs(User::where('email', 'admin@stalltrack.com')->firstOrFail());
    }

    /** @return array<string, string> */
    private function registrationData(): array
    {
        return [
            'first_name' => 'New', 'last_name' => 'Vendor', 'email' => 'new@example.com',
            'mobile_number' => '9171234567', 'password' => 'A-unique-long-passphrase-123',
            'password_confirmation' => 'A-unique-long-passphrase-123', 'terms' => '1', 'role' => 'admin',
        ];
    }

    /** @return array<string, mixed> */
    private function vendorData(Stall $stall): array
    {
        return [
            'name' => 'Vendor', 'stall_number' => $stall->stall_number,
            'market_section' => $stall->market_section, 'monthly_rent' => '3500.00',
            'billing_cycle' => 'Monthly', 'contract_start_date' => '2026-01-01',
            'contract_end_date' => '2026-12-31', 'status' => 'Inactive',
        ];
    }
}
