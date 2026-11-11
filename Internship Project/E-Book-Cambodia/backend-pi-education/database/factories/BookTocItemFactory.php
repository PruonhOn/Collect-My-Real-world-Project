<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookTocItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookTocItem>
 */
class BookTocItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'parent_id' => null,
            'title' => fake()->sentence(3),
            'title_en' => null,
            'page' => fake()->numberBetween(1, 200),
            'position' => 0,
            'depth' => 1,
        ];
    }
}
