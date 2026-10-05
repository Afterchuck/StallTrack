<?php

namespace Database\Factories;

use App\Models\Rental;
use App\Models\Stall;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rental>
 */
class RentalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'stall_id' => Stall::factory(),
            'contract_number' => fake()->unique()->bothify('CTR-########'),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'billing_cycle' => 'Monthly',
            'rent_amount' => '3500.00',
            'status' => 'Active',
        ];
    }
}
