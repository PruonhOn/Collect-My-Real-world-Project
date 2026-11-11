<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseEnrollment>
 */
class CourseEnrollmentFactory extends Factory
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
            'student_id' => Student::factory(),
            'enrolled_on' => fake()->date(),
        ];
    }
}
