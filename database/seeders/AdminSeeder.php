<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo administrator seeding is disabled outside local/testing.');
        }

        $email = 'admin@stalltrack.com';
        $password = config('security.demo_admin_password');

        if (! is_string($password) || trim($password) === '') {
            $password = 'admin123';
        }

        $admin = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

        if ($admin !== null && $admin->role === 'admin' && ! empty($admin->password)) {
            return;
        }

        if ($admin === null) {
            User::create([
                'email' => $email,
                'name' => 'Public Market Admin',
                'password' => Hash::make($password),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);

            return;
        }

        $admin->fill([
            'name' => $admin->name ?: 'Public Market Admin',
            'role' => 'admin',
            'email_verified_at' => $admin->email_verified_at ?? now(),
        ]);

        if (empty($admin->password)) {
            $admin->password = Hash::make($password);
        }

        $admin->save();
    }
}
