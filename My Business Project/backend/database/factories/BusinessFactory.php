<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Business>
 */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5),
            'business_type' => fake()->randomElement(['retail', 'services', 'restaurant', 'consulting']),
            'country' => 'US',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'is_active' => true,
            'is_suspended' => false,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_suspended' => true,
            'suspended_at' => now(),
        ]);
    }
}
