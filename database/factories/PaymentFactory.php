<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
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
            'amount' => '100.00',
            'paid_at' => today(),
            'receipt_number' => fake()->unique()->bothify('REC-########'),
            'payment_method' => 'Cash',
            'status' => 'Paid',
        ];
    }
}
