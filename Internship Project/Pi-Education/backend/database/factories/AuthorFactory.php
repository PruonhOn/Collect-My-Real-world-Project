<?php

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Author>
 */
class AuthorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
        ];
    }

    public function withTranslation(string $locale = 'en'): static
    {
        return $this->afterCreating(function (Author $author) use ($locale) {
            $author->translations()->create([
                'locale' => $locale,
                'name' => fake()->name(),
            ]);
        });
    }
}
