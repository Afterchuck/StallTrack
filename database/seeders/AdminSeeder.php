<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::where('email', 'admin@publicmarket.gov')
            ->update(['email' => 'admin@stalltrack.com']);

        User::updateOrCreate(
            ['email' => 'admin@stalltrack.com'],
            [
                'name' => 'Public Market Admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        );
    }
}
