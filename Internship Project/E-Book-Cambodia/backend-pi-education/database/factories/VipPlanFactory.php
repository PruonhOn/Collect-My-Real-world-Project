<?php

namespace Database\Factories;

use App\Models\VipPlan;
use App\Services\VipService;
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
            'price' => 59.99,
            'duration_days' => VipService::SUBSCRIPTION_DAYS,
            'duration_hours' => null,
            'is_test' => false,
            'is_active' => true,
        ];
    }

    public function testCard(): static
    {
        return $this->state(fn () => [
            'name' => 'VIP Test Card',
            'slug' => VipService::TEST_PLAN_SLUG,
            'description' => 'TEST ONLY - $0.01 - 5 HOURS.',
            'price' => VipService::TEST_PLAN_PRICE,
            'duration_days' => 0,
            'duration_hours' => VipService::TEST_SUBSCRIPTION_HOURS,
            'is_test' => true,
        ]);
    }
}
