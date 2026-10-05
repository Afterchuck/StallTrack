<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        if (app()->environment('local') && $admin && ! Announcement::exists()) {
            Announcement::factory()->for($admin)->create([
                'title' => 'Welcome to market announcements',
                'message' => 'Draft example. Edit this message before publishing it to vendors.',
            ]);
        }
    }
}
