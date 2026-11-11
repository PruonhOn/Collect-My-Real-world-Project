<?php

namespace Database\Factories;

use App\Enums\PartyType;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Party>
 */
class PartyFactory extends Factory
{
    protected $model = Party::class;

    /**
     * Every column is populated.
     *
     * `preventAccessingMissingAttributes` is enabled outside production, so a
     * partial definition would make any read of an omitted column throw. That is
     * the behaviour we want in tests, and it means the factory has to look like
     * a real record rather than a minimal one.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            // The default surface, so a test that forgets `ofType()` is visibly
            // wrong rather than silently exercising a customer list.
            'type' => PartyType::Customer,
            'code' => strtoupper(Str::random(8)),
            'name' => $name,
            'contact_name' => fake()->name(),
            'contact_email' => fake()->unique()->companyEmail(),
            'contact_phone' => fake()->numerify('+1-###-###-####'),
            'tax_number' => strtoupper(Str::random(2)).fake()->numerify('#########'),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional()->buildingNumber(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'postal_code' => fake()->postcode(),
            'country_code' => fake()->randomElement(['SG', 'US', 'DE', 'CN', 'NL', 'GB']),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function ofType(PartyType $type): static
    {
        return $this->state(fn (): array => ['type' => $type]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
