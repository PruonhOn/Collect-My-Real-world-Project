<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
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
            'employee_code' => fake()->unique()->numerify('STF-####'),
            'position' => fake()->optional()->jobTitle(),
            'department' => fake()->optional()->word(),
            'hire_date' => fake()->date(),
        ];
    }
}
