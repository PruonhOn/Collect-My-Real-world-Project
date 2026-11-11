<?php

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Author>
 */
class AuthorFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->optional()->phoneNumber(),
            'status' => Author::STATUS_ACTIVE,
            'bio' => fake()->optional()->paragraph(),
            'specialization' => fake()->optional()->words(3, true),
            'organization' => fake()->optional()->company(),
            'school' => fake()->optional()->company(),
            'social_links' => fake()->optional()->randomElement([
                ['facebook' => 'https://facebook.com/author'],
                ['twitter' => 'https://twitter.com/author'],
                ['website' => 'https://author.example.com'],
            ]),
            'email_verified_at' => now(),
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Author::STATUS_SUSPENDED,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Author::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
    }
}
