<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

#[Signature('security:check')]
#[Description('Read-only deployment security checks; does not replace a penetration test or dependency audit')]
class SecurityCheck extends Command
{
    public function handle(): int
    {
        $failures = 0;
        $checks = [
            'APP_ENV is production' => app()->isProduction(),
            'Debug output is disabled' => ! config('app.debug'),
            'Application encryption key is configured' => filled(config('app.key')),
            'APP_URL uses HTTPS' => parse_url(config('app.url'), PHP_URL_SCHEME) === 'https',
            'Session cookies require HTTPS' => config('session.secure') === true,
            'Session cookies are HTTP-only' => config('session.http_only') === true,
            'SameSite session protection is enabled' => in_array(config('session.same_site'), ['lax', 'strict'], true),
            'Session storage persists across requests' => ! in_array(config('session.driver'), ['array', 'null'], true),
            'Rate-limit cache persists across requests' => ! in_array(config('cache.default'), ['array', 'null'], true),
            'Vite development hot file is absent' => ! is_file(public_path('hot')),
            'No vendor ID photos remain on the public disk' => Storage::disk('public')->allFiles('vendor-photos') === [],
        ];
        foreach ($checks as $label => $passed) {
            if ($passed) {
                $this->info('PASS: '.$label);
            } else {
                $this->error('FAIL: '.$label);
                $failures++;
            }
        }
        try {
            $unsafeAdmin = User::where('role', 'admin')->get(['password'])->contains(function (User $user): bool {
                return Hash::check('admin123', $user->password) || Hash::check('password', $user->password);
            });
            if ($unsafeAdmin) {
                $this->error('FAIL: An administrator still uses a known demo password. Rotate it before hosting.');
                $failures++;
            }
        } catch (\Throwable) {
            $this->error('FAIL: Could not verify administrator credentials against the configured database.');
            $failures++;
        }
        $this->line('Also run composer audit and npm audit. Verify the web root is public/, HTTPS/proxies, backups, server permissions, and database access on the host.');
        $this->line('Registration is '.(config('security.registration_enabled') ? 'enabled: verify identities before assigning rentals.' : 'disabled.'));
        $this->line('Passing these checks is not a guarantee that the deployment is secure.');

        return $failures ? self::FAILURE : self::SUCCESS;
    }
}
