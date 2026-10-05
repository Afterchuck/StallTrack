<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'admin']),
            'title' => fake()->sentence(),
            'message' => fake()->paragraph(),
            'published_at' => null,
            'expires_at' => null,
            'is_pinned' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['published_at' => now()]);
    }
}
