<?php

namespace Database\Factories;

use App\Enums\FeeStatus;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentFee>
 */
class StudentFeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'invoice_no' => fake()->unique()->numerify('INV-########'),
            'title' => fake()->words(3, true),
            'amount' => fake()->numberBetween(50, 1000),
            'paid_amount' => 0,
            'status' => FeeStatus::Unpaid->value,
            'due_date' => fake()->optional()->date(),
        ];
    }

    /**
     * Indicate that the fee has been fully paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid_amount' => $attributes['amount'],
            'status' => FeeStatus::Paid->value,
        ]);
    }

    /**
     * Indicate that the fee has been partially paid.
     */
    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid_amount' => round((float) $attributes['amount'] / 2, 2),
            'status' => FeeStatus::Partial->value,
        ]);
    }
}
