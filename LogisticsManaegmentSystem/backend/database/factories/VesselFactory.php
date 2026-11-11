<?php

namespace Database\Factories;

use App\Models\Vessel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vessel>
 */
class VesselFactory extends Factory
{
    protected $model = Vessel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Str::upper(fake()->word()).' '.fake()->numberBetween(1, 99),
            // Seven digits starting non-zero, matching the controller's rule, so a
            // factory-made vessel is a vessel the API would have accepted.
            'imo_number' => (string) fake()->numberBetween(7000000, 9899999),
            'mmsi' => (string) fake()->numberBetween(100000000, 999999999),
            'flag_country' => fake()->randomElement(['SG', 'MH', 'PA', 'HK', 'CY']),
            'vessel_type' => fake()->randomElement(['Container Ship', 'Bulk Carrier', 'General Cargo', 'Reefer']),
            'length_meters' => fake()->numberBetween(120, 400),
            'beam_meters' => fake()->numberBetween(18, 60),
            'capacity_teu' => fake()->numberBetween(1000, 20000),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
