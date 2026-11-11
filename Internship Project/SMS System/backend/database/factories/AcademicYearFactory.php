<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->unique()->numberBetween(2020, 2099);
        $endYear = $startYear + 1;

        return [
            'name' => "{$startYear}-{$endYear}",
            'start_date' => "{$startYear}-09-01",
            'end_date' => "{$endYear}-07-31",
            'is_current' => false,
        ];
    }

    /**
     * Indicate that this is the current academic year.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
        ]);
    }
}
