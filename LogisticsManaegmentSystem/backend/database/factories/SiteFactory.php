<?php

namespace Database\Factories;

use App\Enums\SiteKind;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    /**
     * Defaults to a warehouse. `ofKind()` switches it, and clears the two
     * warehouse-only columns so a factory-made factory does not carry a
     * meaningless pallet capacity - the API rejects those fields outright.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => SiteKind::Warehouse,
            'code' => 'WH-'.Str::random(6),
            'name' => fake()->company().' Logistics Centre',
            'contact_name' => fake()->name(),
            'contact_email' => fake()->unique()->companyEmail(),
            'contact_phone' => fake()->numerify('+1-###-###-####'),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => null,
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'postal_code' => fake()->postcode(),
            'country_code' => fake()->randomElement(['SG', 'US', 'DE', 'CN', 'NL', 'GB']),
            'site_type' => fake()->randomElement(['general', 'bonded', 'cold_store', 'transhipment']),
            'capacity_pallets' => fake()->numberBetween(50, 5000),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function ofKind(SiteKind $kind): static
    {
        return $this->state(fn (): array => $kind === SiteKind::Factory
            ? ['kind' => $kind, 'site_type' => null, 'capacity_pallets' => null]
            : ['kind' => $kind]);
    }

    public function bonded(): static
    {
        return $this->state(fn (): array => ['site_type' => 'bonded']);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
