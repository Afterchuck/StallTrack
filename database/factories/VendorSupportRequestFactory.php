<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VendorSupportRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorSupportRequest>
 */
class VendorSupportRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => fake()->sentence(5),
            'category' => fake()->randomElement(['Account', 'Stall', 'Payments', 'Rentals and contracts', 'Other']),
            'message' => fake()->paragraph(),
            'status' => 'Open',
            'admin_response' => null,
            'responded_by' => null,
            'responded_at' => null,
        ];
    }
}
