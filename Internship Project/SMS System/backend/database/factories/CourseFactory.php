<?php

namespace Database\Factories;

use App\Enums\CourseStatus;
use App\Enums\CourseType;
use App\Models\AcademicYear;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => fake()->unique()->words(3, true),
            'code' => fake()->unique()->lexify('CRS-???'),
            'description' => fake()->optional()->paragraph(),
            'type' => CourseType::FaceToFace->value,
            'fee' => fake()->numberBetween(100, 2000),
            'capacity' => 30,
            'status' => CourseStatus::Active->value,
            'featured' => false,
            'is_published' => true,
            'start_date' => fake()->date(),
            'end_date' => fake()->optional()->date(),
        ];
    }

    /**
     * Indicate that the course is featured on the public site.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured' => true,
        ]);
    }

    /**
     * Indicate that the course is not published on the public site.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }

    /**
     * Indicate that the course is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::Archived->value,
        ]);
    }
}
