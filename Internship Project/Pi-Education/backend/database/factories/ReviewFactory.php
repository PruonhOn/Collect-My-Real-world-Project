<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->sentence(12),
            'status' => Review::STATUS_APPROVED,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => Review::STATUS_PENDING,
        ]);
    }
}
