<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
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
            'employee_code' => fake()->unique()->numerify('TCH-####'),
            'hire_date' => fake()->date(),
            'qualification' => fake()->optional()->words(3, true),
            'specialization' => fake()->optional()->words(2, true),
            'bio' => fake()->optional()->paragraph(),
        ];
    }
}
