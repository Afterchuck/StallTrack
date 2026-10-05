<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bill>
 */
class BillFactory extends Factory
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
            'vendor_name' => fake()->name(),
            'stall_number' => 'A-01',
            'period_start' => today()->startOfMonth(),
            'period_end' => today()->endOfMonth(),
            'due_date' => today()->endOfMonth(),
            'amount' => '3500.00',
            'paid_amount' => '0.00',
        ];
    }
}
