<?php

namespace Database\Seeders;

use App\Models\VendorSupportRequest;
use Illuminate\Database\Seeder;

class VendorSupportRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        VendorSupportRequest::factory()->count(3)->create();
    }
}
