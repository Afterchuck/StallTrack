<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_password_recovery(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'))
            ->assertSee('Forgot password?');
    }

    public function test_reset_link_can_be_sent_for_vendor_and_admin_accounts(): void
    {
        Notification::fake();

        foreach (['vendor', 'admin'] as $role) {
            $user = User::factory()->create([
                'email' => $role.'@example.com',
                'role' => $role,
            ]);

            $this->post(route('password.email'), ['email' => $user->email])
                ->assertRedirect()
                ->assertSessionHas('status', 'If an account exists for that email, a password reset link has been sent.');

            Notification::assertSentTo($user, ResetPassword::class);
        }
    }

    public function test_reset_link_request_does_not_disclose_unknown_accounts(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'missing@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status', 'If an account exists for that email, a password reset link has been sent.');

        Notification::assertNothingSent();
    }

    public function test_valid_reset_link_changes_password_for_vendor_and_admin(): void
    {
        Notification::fake();

        foreach (['vendor', 'admin'] as $role) {
            $email = $role.'@example.com';
            $user = User::factory()->create(['email' => $email, 'role' => $role]);
            if ($role === 'vendor') {
                Vendor::factory()->for($user)->create(['email' => $email, 'approval_status' => 'Approved']);
            }
            $this->post(route('password.email'), ['email' => $email]);

            $token = null;
            Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            });
            $this->assertNotNull($token);

            $this->get(route('password.reset', ['token' => $token, 'email' => $email]))
                ->assertOk()
                ->assertSee('Choose a new password');

            $this->post(route('password.update'), [
                'token' => $token,
                'email' => $email,
                'password' => 'A-new-secure-passphrase-123',
                'password_confirmation' => 'A-new-secure-passphrase-123',
            ])->assertRedirect(route('login'));

            $this->assertTrue(Hash::check('A-new-secure-passphrase-123', $user->fresh()->password));
            $this->post(route('login.store'), [
                'email' => $email,
                'password' => 'A-new-secure-passphrase-123',
            ])->assertRedirect($role === 'vendor' ? route('vendor.dashboard') : route('dashboard'));
            auth()->logout();
        }
    }

    public function test_reset_rejects_invalid_tokens_and_short_passwords(): void
    {
        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'missing@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'missing@example.com',
            'password' => 'A-new-secure-passphrase-123',
            'password_confirmation' => 'A-new-secure-passphrase-123',
        ])->assertSessionHasErrors('email');
    }
}
