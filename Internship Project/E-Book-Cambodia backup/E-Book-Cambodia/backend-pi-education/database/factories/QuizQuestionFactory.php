<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    public function definition(): array
    {
        $correctOption = fake()->numberBetween(0, 3);

        return [
            'quiz_id' => Quiz::factory(),
            'question' => fake()->sentence().'?',
            'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
            'correct_option' => $correctOption,
            'position' => 1,
        ];
    }
}
