<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('+1 day', '+3 months');
        $feeType = fake()->randomElement([Event::FEE_FREE, Event::FEE_PAID]);

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'cover_image' => null,
            'category' => fake()->optional()->randomElement(['Education', 'Community', 'Workshop', 'Competition', 'Workshop']),
            'start_date' => $startDate,
            'end_date' => fake()->optional()->dateTimeBetween($startDate, '+3 months'),
            'location' => fake()->city(),
            'organizer' => fake()->optional()->company(),
            'registration_link' => fake()->optional()->url(),
            'contact_phone' => fake()->optional()->phoneNumber(),
            'contact_email' => fake()->optional()->safeEmail(),
            'max_participants' => fake()->optional()->numberBetween(10, 500),
            'fee_type' => $feeType,
            'fee_amount' => $feeType === Event::FEE_PAID ? fake()->randomFloat(2, 1, 100) : null,
            'speaker' => fake()->optional()->name(),
            'social_links' => fake()->optional()->randomElements([fake()->url(), fake()->url()], 1),
            'status' => Event::STATUS_PUBLISHED,
            'is_featured' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Event::STATUS_DRAFT,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Event::STATUS_PUBLISHED,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Event::STATUS_CANCELLED,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Event::STATUS_COMPLETED,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
