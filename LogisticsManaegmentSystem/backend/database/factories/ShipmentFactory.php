<?php

namespace Database\Factories;

use App\Enums\Incoterm;
use App\Enums\PartyType;
use App\Enums\ShipmentStatus;
use App\Models\Carrier;
use App\Models\Party;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    /**
     * A shipment needs a reference that is actually unique, and the parties need
     * to be the right *kind* of party - a shipper is not a customer, and a
     * factory that picked at random would produce records the API would then
     * reject on a type check.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'SHP-'.Str::upper(Str::random(6)),
            'status' => fake()->randomElement(ShipmentStatus::values()),
            'shipper_id' => Party::factory()->ofType(PartyType::Shipper),
            'consignee_id' => Party::factory()->ofType(PartyType::Consignee),
            'carrier_id' => Carrier::factory(),
            'vessel_id' => null,
            'port_of_loading_id' => null,
            'port_of_discharge_id' => null,
            'origin_site_id' => null,
            'destination_site_id' => null,
            'truck_id' => null,
            'driver_id' => null,
            'incoterm' => fake()->randomElement(Incoterm::values()),
            'incoterm_place' => fake()->city(),
            'cargo_description' => fake()->randomElement([
                'Electronics',
                'Textiles',
                'Auto parts',
                'Frozen seafood',
                'Furniture',
                'Pharmaceuticals',
            ]),
            'pieces' => fake()->numberBetween(1, 500),
            'weight_kg' => fake()->numberBetween(50, 20000),
            'volume_cbm' => fake()->randomFloat(3, 0.5, 40),
            'etd' => fake()->dateTimeBetween('+1 day', '+3 weeks')->format('Y-m-d'),
            'eta' => fake()->dateTimeBetween('+2 weeks', '+6 weeks')->format('Y-m-d'),
            'actual_departure' => null,
            'actual_arrival' => null,
            'notes' => null,
        ];
    }

    public function withStatus(ShipmentStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    public function draft(): static
    {
        return $this->withStatus(ShipmentStatus::Draft);
    }

    /**
     * A record somebody started and has not finished: no carrier, no dates.
     */
    public function unassigned(): static
    {
        return $this->state(fn (): array => [
            'carrier_id' => null,
            'etd' => null,
            'eta' => null,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'status' => ShipmentStatus::InTransit,
            'etd' => now()->subDays(30)->format('Y-m-d'),
            'eta' => now()->subDays(3)->format('Y-m-d'),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (): array => [
            'status' => ShipmentStatus::Delivered,
            'actual_departure' => now()->subDays(20),
            'actual_arrival' => now()->subDays(2),
        ]);
    }
}
