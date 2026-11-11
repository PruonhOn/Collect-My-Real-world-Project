<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(4)),
            'description' => fake()->paragraphs(2, true),
            'author' => fake()->name(),
            'language' => 'km',
            'pages' => fake()->numberBetween(50, 600),
            'price' => fake()->randomElement([0, 2.99, 4.99, 7.99, 9.99]),
            'is_free' => false,
            'is_published' => true,
            'file_size' => fake()->numberBetween(200000, 5000000),
            'file_type' => 'application/pdf',
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => 0,
            'is_free' => true,
        ]);
    }
}
