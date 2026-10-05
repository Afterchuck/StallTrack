<?php

namespace Database\Factories;

use App\Models\Stall;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stall>
 */
class StallFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stall_number' => fake()->unique()->bothify('A-#####'),
            'market_section' => 'Dry goods',
            'location' => 'North wing',
            'dimensions' => '3m × 3m',
            'monthly_rate' => '3500.00',
            'status' => 'Occupied',
        ];
    }
}
