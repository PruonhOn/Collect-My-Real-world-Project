<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Score;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Score>
 */
class ScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'student_id' => Student::factory(),
            'score' => fake()->randomFloat(2, 0, 100),
            'remark' => fake()->optional()->sentence(),
        ];
    }
}
