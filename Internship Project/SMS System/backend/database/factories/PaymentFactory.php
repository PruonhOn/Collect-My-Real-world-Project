<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentFee;
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
            'student_fee_id' => StudentFee::factory(),
            'student_id' => Student::factory(),
            'amount' => fake()->numberBetween(10, 500),
            'method' => 'manual',
            'status' => PaymentStatus::Confirmed->value,
            'paid_on' => fake()->dateTime(),
        ];
    }

    /**
     * Indicate that the payment is pending confirmation.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Pending->value,
            'paid_on' => null,
        ]);
    }
}
