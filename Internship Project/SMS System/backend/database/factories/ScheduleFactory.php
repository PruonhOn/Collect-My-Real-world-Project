<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
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
            'day_of_week' => fake()->numberBetween(0, 6),
            'start_time' => fake()->time('H:i'),
            'end_time' => fake()->time('H:i'),
            'room' => fake()->optional()->bothify('Room ##'),
        ];
    }
}
