<?php

namespace Database\Factories;

use App\Enums\CarrierType;
use App\Models\Carrier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Carrier>
 */
class CarrierFactory extends Factory
{
    protected $model = Carrier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $suffix = strtoupper(Str::random(3));

        return [
            'code' => 'CAR-'.Str::random(6),
            'name' => fake()->company().' Logistics',
            'scac' => $suffix,
            'type' => fake()->randomElement(CarrierType::cases()),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->unique()->companyEmail(),
            'contact_phone' => fake()->numerify('+1-###-###-####'),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => null,
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'postal_code' => fake()->postcode(),
            'country_code' => fake()->randomElement(['SG', 'US', 'DE', 'CN', 'NL', 'GB']),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function ofType(CarrierType $type): static
    {
        return $this->state(fn (): array => ['type' => $type]);
    }

    public function withoutScac(): static
    {
        return $this->state(fn (): array => ['scac' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
