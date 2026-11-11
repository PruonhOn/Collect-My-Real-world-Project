<?php

namespace Database\Factories;

use App\Models\VipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VipPlan>
 */
class VipPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->sentence(),
            'price' => fake()->randomElement([7.99, 59.99]),
            'duration_days' => fake()->randomElement([30, 365]),
            'is_active' => true,
        ];
    }
}
