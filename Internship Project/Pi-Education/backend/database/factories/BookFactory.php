<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'type' => Book::TYPE_FREE,
            'price' => 0,
            'status' => Book::STATUS_PUBLISHED,
            'language' => 'km',
            'published_at' => now(),
            'reader_count' => 0,
        ];
    }

    public function paid(float $price = 9.99): static
    {
        return $this->state(fn () => [
            'type' => Book::TYPE_PAID,
            'price' => $price,
        ]);
    }

    public function withTranslation(string $locale = 'en', ?string $title = null): static
    {
        return $this->afterCreating(function (Book $book) use ($locale, $title) {
            $book->translations()->create([
                'locale' => $locale,
                'title' => $title ?: fake()->sentence(3),
                'description' => fake()->paragraph(),
                'content' => fake()->paragraphs(4, true),
            ]);
        });
    }
}
