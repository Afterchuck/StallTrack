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
        if (User::where('email', 'admin@stalltrack.com')->exists()) {
            return;
        }
        $password = config('security.demo_admin_password');
        if (! is_string($password) || strlen($password) < 12 || strlen($password) > 72) {
            throw new RuntimeException('Set DEMO_ADMIN_PASSWORD to a unique 12–72 byte password before local seeding.');
        }
        User::create([
            'email' => 'admin@stalltrack.com', 'name' => 'Public Market Admin',
            'password' => Hash::make($password), 'role' => 'admin', 'email_verified_at' => now(),
        ]);
    }
}
