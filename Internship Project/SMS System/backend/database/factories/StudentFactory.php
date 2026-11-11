<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
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
            'student_code' => fake()->unique()->numerify('STU-####'),
            'khmer_name' => fake()->optional()->name(),
            'gender' => fake()->randomElement(Gender::cases())->value,
            'birth_date' => fake()->date(max: '-7 years'),
            'contact_phone' => fake()->optional()->phoneNumber(),
            'guardian_name' => fake()->optional()->name(),
            'guardian_phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->address(),
            'status' => StudentStatus::Active->value,
            'enrolled_on' => fake()->date(),
        ];
    }
}
