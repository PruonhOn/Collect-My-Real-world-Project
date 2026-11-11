<?php

namespace Database\Factories;

use App\Models\BroadcastMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BroadcastMessage>
 */
class BroadcastMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'type' => BroadcastMessage::TYPE_BANNER,
            'tone' => BroadcastMessage::TONE_INFO,
            'link_url' => null,
            'link_label' => null,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function popup(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => BroadcastMessage::TYPE_POPUP,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'ends_at' => now()->subMinute(),
        ]);
    }

    public function scheduledForTomorrow(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->addDay(),
        ]);
    }
}
