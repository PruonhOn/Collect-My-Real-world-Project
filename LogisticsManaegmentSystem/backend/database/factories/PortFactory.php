<?php

namespace Database\Factories;

use App\Models\Port;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Port>
 */
class PortFactory extends Factory
{
    protected $model = Port::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $country = fake()->randomElement(['SG', 'US', 'DE', 'CN', 'NL', 'GB', 'AE']);

        return [
            // Four-letter UN/LOCODE shape, built from the same country code that
            // lands in country_code so a generated port is internally consistent.
            'un_locode' => $country.Str::upper(fake()->lexify('??')),
            'name' => 'Port of '.fake()->city(),
            'country_code' => $country,
            'port_type' => fake()->randomElement(['Container Terminal', 'Bulk Terminal', 'General Cargo', 'Transhipment']),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'timezone' => fake()->timezone(),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inCountry(string $countryCode): static
    {
        return $this->state(fn (): array => [
            'country_code' => strtoupper($countryCode),
            'un_locode' => strtoupper($countryCode).Str::upper(fake()->lexify('??')),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
