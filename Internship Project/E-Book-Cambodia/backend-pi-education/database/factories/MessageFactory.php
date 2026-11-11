<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => fake()->sentence(3),
            'message' => fake()->paragraph(),
            'reply' => null,
            'replied_at' => null,
        ];
    }

    public function replied(): static
    {
        return $this->state(fn (array $attributes) => [
            'reply' => fake()->paragraph(),
            'replied_at' => now(),
        ]);
    }
}
