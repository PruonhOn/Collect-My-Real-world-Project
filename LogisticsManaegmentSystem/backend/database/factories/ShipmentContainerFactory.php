<?php

namespace Database\Factories;

use App\Enums\ContainerStatus;
use App\Models\Shipment;
use App\Models\ShipmentContainer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentContainer>
 */
class ShipmentContainerFactory extends Factory
{
    protected $model = ShipmentContainer::class;

    /**
     * A box that has been booked but is not carrying anything yet.
     *
     * `empty` rather than a random status, for the same reason the tracking factory
     * defaults to no milestone: it is the honest state of equipment the moment it is
     * allocated, and it is the state a bookings office spends most of its time
     * looking at. A factory that started boxes mid-voyage would hide the case the
     * module exists to show.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prefix = strtoupper(fake()->randomElement(['MSCU', 'TGHU', 'CMAU', 'HLXU', 'OOLU', 'EGHU']));

        return [
            'shipment_id' => Shipment::factory(),
            'container_number' => $prefix.fake()->unique()->numerify('#######-#'),
            'iso_code' => fake()->randomElement(['22G1', '42G1', '45G1', '22R1', '42R1', '22P1', '42U1']),
            'type' => null,
            'status' => ContainerStatus::Empty,
            'seal_number' => null,
            'tare_weight_kg' => null,
            'max_gross_weight_kg' => null,
            'gross_weight_kg' => null,
            'notes' => null,
        ];
    }

    /**
     * Packed and sealed, waiting to move.
     */
    public function loaded(): static
    {
        return $this->state(fn (): array => [
            'status' => ContainerStatus::Loaded,
            'seal_number' => strtoupper(fake()->bothify('SEAL####??')),
        ]);
    }

    /**
     * On the water or on the road, with the figures a loaded box is weighed for.
     *
     * The weights are set together because that is how they are read: a carrier
     * reports a gross weight off a weighbridge and the tare is stencilled on the
     * door, so one of the two without the other is a box nobody can say anything
     * about. The gross is derived from the rating so `utilisationPercent()` is
     * always a sensible figure rather than a random ratio.
     */
    public function inTransit(): static
    {
        return $this->state(fn (): array => [
            'status' => ContainerStatus::InTransit,
            'seal_number' => strtoupper(fake()->bothify('SEAL####??')),
            'tare_weight_kg' => fake()->randomElement([2250, 3700, 3900, 4600]),
            'max_gross_weight_kg' => 30480,
            'gross_weight_kg' => fake()->numberBetween(9000, 30480),
        ]);
    }

    /**
     * At a border, not permitted past customs.
     *
     * A delay reason with it, because a hold is always somebody's problem to chase
     * and a bare status gives an operator nothing to phone about.
     */
    public function customsHold(): static
    {
        return $this->inTransit()->state(fn (): array => [
            'status' => ContainerStatus::CustomsHold,
            'notes' => fake()->randomElement([
                'Held for documentary check',
                'Physical examination ordered',
                'Missing packing list',
                'Valuation under review',
            ]),
        ]);
    }

    /**
     * Out of customs and available for collection.
     */
    public function released(): static
    {
        return $this->inTransit()->state(fn (): array => [
            'status' => ContainerStatus::Released,
        ]);
    }

    /**
     * Handed to the consignee. The end of this box's involvement.
     */
    public function delivered(): static
    {
        return $this->released()->state(fn (): array => [
            'status' => ContainerStatus::Delivered,
        ]);
    }

    /**
     * Written off, and not moving.
     */
    public function damaged(): static
    {
        return $this->inTransit()->state(fn (): array => [
            'status' => ContainerStatus::Damaged,
            'notes' => fake()->randomElement([
                'Roof panel torn - unusable',
                'Corner casting cracked',
                'Doors will not close - seal broken',
                'Container submerged, written off',
            ]),
        ]);
    }
}
