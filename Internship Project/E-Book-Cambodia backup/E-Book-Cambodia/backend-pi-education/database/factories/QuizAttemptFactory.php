<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'quiz_id' => Quiz::factory(),
            'score' => fake()->numberBetween(0, 10),
            'total' => 10,
            'answers' => [],
            'passed' => fake()->boolean(),
            'completed_at' => now(),
        ];
    }
}
