<?php

namespace Database\Factories;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->unique()->words(3, true),
            'type' => AssessmentType::Quiz->value,
            'max_score' => 100,
            'weight' => fake()->randomFloat(2, 0, 20),
            'date' => fake()->optional()->date(),
        ];
    }
}
