<?php

namespace Database\Factories;

use App\Models\Carrier;
use App\Models\Truck;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Truck>
 */
class TruckFactory extends Factory
{
    protected $model = Truck::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plate_number' => strtoupper(fake()->bothify('??-####??')),
            'code' => 'TRK-'.Str::random(6),
            'carrier_id' => Carrier::factory(),
            'truck_type' => fake()->randomElement(['tractor', 'rigid', 'box_truck', 'flatbed', 'reefer', 'tanker']),
            'capacity_kg' => fake()->numberBetween(1000, 26000),
            'capacity_pallets' => fake()->numberBetween(2, 26),
            'make' => fake()->randomElement(['Volvo', 'Scania', 'Mercedes-Benz', 'MAN', 'DAF']),
            'model' => Str::upper(fake()->lexify('???')).fake()->numberBetween(100, 999),
            'year' => (string) fake()->numberBetween(2015, (int) date('Y')),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function forCarrier(Carrier|int|null $carrier): static
    {
        return $this->state(fn (): array => [
            'carrier_id' => $carrier instanceof Carrier ? $carrier->getKey() : $carrier,
        ]);
    }

    public function withoutCarrier(): static
    {
        return $this->forCarrier(null);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
