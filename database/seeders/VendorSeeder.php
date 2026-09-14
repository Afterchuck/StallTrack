<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            ['name' => 'Elena Rostova', 'stall_number' => 'A-102', 'market_section' => 'Fresh Produce', 'contact_number' => '+63 917 555 0192', 'email' => 'elena@example.com', 'monthly_rent' => 3500, 'status' => 'Active'],
            ['name' => 'Marcus Chen', 'stall_number' => 'B-045', 'market_section' => 'Dry Goods', 'contact_number' => '+63 920 888 2314', 'email' => 'marcus@example.com', 'monthly_rent' => 4200, 'status' => 'Active'],
            ['name' => 'Teresa Alcantara', 'stall_number' => 'C-110', 'market_section' => 'General Merchandise', 'contact_number' => '+63 945 119 4022', 'email' => 'teresa@example.com', 'monthly_rent' => 3800, 'status' => 'Active'],
            ['name' => 'Danilo Santos', 'stall_number' => 'M-012', 'market_section' => 'Wet Market', 'contact_number' => '+63 918 340 7820', 'email' => 'danilo@example.com', 'monthly_rent' => 5100, 'status' => 'Inactive'],
            ['name' => 'Mateo Reyes', 'stall_number' => 'D-102', 'market_section' => 'Food Court', 'contact_number' => '+63 917 456 7821', 'email' => 'mateo@example.com', 'monthly_rent' => 4500, 'status' => 'Active'],
            ['name' => 'Ana Villareal', 'stall_number' => 'A-014', 'market_section' => 'Fresh Produce', 'contact_number' => '+63 905 321 6754', 'email' => 'ana@example.com', 'monthly_rent' => 3000, 'status' => 'Active'],
            ['name' => 'Rosa Delacruz', 'stall_number' => 'B-021', 'market_section' => 'Dry Goods', 'contact_number' => '+63 906 111 5432', 'email' => 'rosa@example.com', 'monthly_rent' => 2800, 'status' => 'Active'],
            ['name' => 'Juan Dela Cruz', 'stall_number' => 'C-028', 'market_section' => 'General Merchandise', 'contact_number' => '+63 907 222 1098', 'email' => 'juan@example.com', 'monthly_rent' => 2650, 'status' => 'Active'],
        ];

        foreach ($vendors as $vendor) {
            Vendor::updateOrCreate(['name' => $vendor['name']], $vendor);
        }
    }
}
