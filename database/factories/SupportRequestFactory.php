<?php

namespace Database\Factories;

use App\Models\SupportRequest;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportRequest>
 */
class SupportRequestFactory extends Factory
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
            'topic' => fake()->randomElement(['Billing & payments', 'Stall & lease', 'Account access', 'Other']),
            'subject' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'status' => 'Open',
        ];
    }
}
