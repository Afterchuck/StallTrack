<?php

namespace Database\Seeders;

use App\Models\Rental;
use App\Models\Stall;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class StallSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stalls = [
            [
                'stall_number' => 'A-102',
                'market_section' => 'Fresh Produce',
                'location' => 'Building A, Ground Floor',
                'stall_type' => 'Standard',
                'dimensions' => '3m x 3m',
                'monthly_rate' => 3500.00,
                'status' => 'Occupied',
            ],
            [
                'stall_number' => 'B-045',
                'market_section' => 'Dry Goods',
                'location' => 'Building B, Aisle 2',
                'stall_type' => 'Corner Stall',
                'dimensions' => '4m x 3m',
                'monthly_rate' => 4200.00,
                'status' => 'Occupied',
            ],
            [
                'stall_number' => 'C-110',
                'market_section' => 'General Merchandise',
                'location' => 'Building C, Center Wing',
                'stall_type' => 'Standard',
                'dimensions' => '3m x 3m',
                'monthly_rate' => 3800.00,
                'status' => 'Occupied',
            ],
            [
                'stall_number' => 'M-012',
                'market_section' => 'Wet Market',
                'location' => 'Meat & Seafood Section',
                'stall_type' => 'Wet Stall',
                'dimensions' => '3.5m x 3m',
                'monthly_rate' => 5100.00,
                'status' => 'Inactive',
            ],
            [
                'stall_number' => 'D-102',
                'market_section' => 'Food Court',
                'location' => 'Food Hall, Unit 2',
                'stall_type' => 'Food Stall',
                'dimensions' => '4m x 4m',
                'monthly_rate' => 4500.00,
                'status' => 'Occupied',
            ],
            [
                'stall_number' => 'A-014',
                'market_section' => 'Fresh Produce',
                'location' => 'Building A, East Entrance',
                'stall_type' => 'Standard',
                'dimensions' => '3m x 2.5m',
                'monthly_rate' => 3000.00,
                'status' => 'Occupied',
            ],
            [
                'stall_number' => 'B-021',
                'market_section' => 'Dry Goods',
                'location' => 'Building B, Aisle 1',
                'stall_type' => 'Standard',
                'dimensions' => '3m x 2.5m',
                'monthly_rate' => 2800.00,
                'status' => 'Occupied',
            ],
            [
                'stall_number' => 'C-028',
                'market_section' => 'General Merchandise',
                'location' => 'Building C, West Wing',
                'stall_type' => 'Standard',
                'dimensions' => '2.5m x 2.5m',
                'monthly_rate' => 2650.00,
                'status' => 'Occupied',
            ],
            [
                'stall_number' => 'A-105',
                'market_section' => 'Fresh Produce',
                'location' => 'Building A, Ground Floor',
                'stall_type' => 'Standard',
                'dimensions' => '3m x 3m',
                'monthly_rate' => 3500.00,
                'status' => 'Available',
            ],
            [
                'stall_number' => 'B-050',
                'market_section' => 'Dry Goods',
                'location' => 'Building B, Aisle 3',
                'stall_type' => 'Corner Stall',
                'dimensions' => '4m x 3m',
                'monthly_rate' => 4200.00,
                'status' => 'Available',
            ],
            [
                'stall_number' => 'C-115',
                'market_section' => 'General Merchandise',
                'location' => 'Building C, North Wing',
                'stall_type' => 'Standard',
                'dimensions' => '3m x 3m',
                'monthly_rate' => 3800.00,
                'status' => 'Available',
            ],
            [
                'stall_number' => 'M-018',
                'market_section' => 'Wet Market',
                'location' => 'Meat & Seafood Section',
                'stall_type' => 'Wet Stall',
                'dimensions' => '3.5m x 3m',
                'monthly_rate' => 5000.00,
                'status' => 'Available',
            ],
            [
                'stall_number' => 'D-108',
                'market_section' => 'Food Court',
                'location' => 'Food Hall, Unit 8',
                'stall_type' => 'Food Stall',
                'dimensions' => '4m x 4m',
                'monthly_rate' => 4500.00,
                'status' => 'Inactive',
            ],
            [
                'stall_number' => 'E-001',
                'market_section' => 'Apparel & Footwear',
                'location' => 'Building E, 2nd Floor',
                'stall_type' => 'Kiosk',
                'dimensions' => '2m x 2m',
                'monthly_rate' => 2500.00,
                'status' => 'Available',
            ],
        ];

        foreach ($stalls as $stallData) {
            $stall = Stall::updateOrCreate(
                ['stall_number' => $stallData['stall_number']],
                $stallData
            );

            $vendor = Vendor::where('stall_number', $stallData['stall_number'])->first();

            if ($vendor && $stallData['status'] === 'Occupied') {
                Rental::updateOrCreate(
                    ['contract_number' => 'RNT-'.str_replace('-', '', (string) $stall->stall_number)],
                    [
                        'vendor_id' => $vendor->id,
                        'stall_id' => $stall->id,
                        'start_date' => now()->startOfYear()->toDateString(),
                        'end_date' => now()->endOfYear()->toDateString(),
                        'rent_amount' => $stall->monthly_rate ?? $vendor->monthly_rent ?? 3000,
                        'billing_cycle' => 'Monthly',
                        'status' => 'Active',
                    ]
                );
            } elseif ($vendor && $stallData['status'] === 'Inactive') {
                Rental::updateOrCreate(
                    ['contract_number' => 'RNT-'.str_replace('-', '', (string) $stall->stall_number)],
                    [
                        'vendor_id' => $vendor->id,
                        'stall_id' => $stall->id,
                        'start_date' => now()->subYear()->startOfYear()->toDateString(),
                        'end_date' => now()->subMonths(2)->toDateString(),
                        'rent_amount' => $stall->monthly_rate ?? $vendor->monthly_rent ?? 5100,
                        'billing_cycle' => 'Monthly',
                        'status' => 'Expired',
                    ]
                );
            }
        }
    }
}
