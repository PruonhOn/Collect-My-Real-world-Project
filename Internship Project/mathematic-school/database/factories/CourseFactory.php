<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'title' => fake()->sentence(4),
            'course_code' => 'CRS-' . strtoupper(fake()->unique()->bothify('???###')),
            'slug' => Str::slug(fake()->unique()->sentence(3)),
            'description' => fake()->paragraph(),
            'age_group' => '8-10',
            'level' => 'beginner',
            'duration' => 12,
            'price' => 10,
            'status' => 'published',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }
}
