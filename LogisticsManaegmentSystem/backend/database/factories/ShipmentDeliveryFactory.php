<?php

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Models\Driver;
use App\Models\Shipment;
use App\Models\ShipmentDelivery;
use App\Models\Truck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentDelivery>
 */
class ShipmentDeliveryFactory extends Factory
{
    protected $model = ShipmentDelivery::class;

    /**
     * A slot booked with a carrier, nothing collected and no vehicle assigned yet.
     *
     * `scheduled` with null timestamps and null vehicle, because that is the honest
     * state of a delivery the moment it is booked, and it is the state an operations
     * desk spends most of its time looking at. A factory that started deliveries
     * in transit would hide the overdue worklist's most common row.
     *
     * The reference is generated to look like ours - `DLV-2026-000001` per
     * `logistics.numbering.delivery` - because the column is unique and a factory
     * that emitted a constant would collide with itself. It is still supplied by the
     * client in the API; see the model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'reference' => 'DLV-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'status' => DeliveryStatus::Scheduled,
            'truck_id' => null,
            'driver_id' => null,
            'scheduled_at' => null,
            'picked_up_at' => null,
            'delivered_at' => null,
            'contact_name' => null,
            'contact_phone' => null,
            'delivery_address' => null,
            'pod_reference' => null,
            'failure_reason' => null,
            'notes' => null,
        ];
    }

    /**
     * A slot in the future, with a vehicle planned against it.
     */
    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'status' => DeliveryStatus::Scheduled,
            'scheduled_at' => now()->addDays(fake()->numberBetween(1, 9))->setTime(fake()->numberBetween(8, 17), 0),
            'truck_id' => Truck::factory(),
            'driver_id' => Driver::factory(),
        ]);
    }

    /**
     * The slot has passed and the cargo has not been handed over: the worklist.
     */
    public function overdue(): static
    {
        return $this->scheduled()->state(fn (): array => [
            // A day past at the earliest, so there is no ambiguity about which side of
            // the line a row sits on when the suite is running slowly.
            'scheduled_at' => now()->subDays(fake()->numberBetween(1, 12))->setTime(fake()->numberBetween(8, 17), 0),
        ]);
    }

    /**
     * The vehicle has gone to collect the cargo and has not picked it up.
     */
    public function pickingUp(): static
    {
        return $this->scheduled()->state(fn (): array => [
            'status' => DeliveryStatus::PickingUp,
        ]);
    }

    /**
     * The vehicle has the cargo and is on the road, with a slot in the past.
     *
     * Both timestamps set, because a delivery that is on the road without a slot is
     * not a case that occurs.
     */
    public function inTransit(): static
    {
        return $this->scheduled()->state(fn (): array => [
            'status' => DeliveryStatus::InTransit,
            'scheduled_at' => now()->subHours(fake()->numberBetween(3, 40)),
            'picked_up_at' => now()->subMinutes(fake()->numberBetween(20, 180)),
        ]);
    }

    /**
     * Handed over. The end of this row's life, but not of the consignment's.
     */
    public function delivered(): static
    {
        return $this->inTransit()->state(fn (): array => [
            'status' => DeliveryStatus::Delivered,
            'delivered_at' => now()->subMinutes(fake()->numberBetween(5, 240)),
            'pod_reference' => fake()->unique()->numerify('POD-######'),
            'contact_name' => fake()->randomElement(['Site manager', 'Goods-in', 'Duty pharmacist', 'Store keeper']),
        ]);
    }

    /**
     * Attempted and could not be completed.
     *
     * The cargo is not with the consignee, so `delivered_at` is deliberately left
     * null even though `picked_up_at` is set: the collection happened and the handover
     * did not, and collapsing them would make a failed drop indistinguishable from a
     * successful one that took a while.
     */
    public function failed(): static
    {
        return $this->inTransit()->state(fn (): array => [
            'status' => DeliveryStatus::Failed,
            'failure_reason' => fake()->randomElement([
                'Nobody available to sign',
                'Consignee refused - wrong consignment on the truck',
                'Site gate closed, no access',
                'Address not found',
                'Refused: price discrepancy on the POD',
            ]),
        ]);
    }

    /**
     * Called off before it happened.
     */
    public function cancelled(): static
    {
        return $this->scheduled()->state(fn (): array => [
            'status' => DeliveryStatus::Cancelled,
        ]);
    }

    /**
     * Somewhere other than the head office: the case the delivery address column
     * exists for.
     */
    public function toSite(?string $name = null): static
    {
        return $this->state(fn (): array => [
            'contact_name' => $name ?? fake()->randomElement(['Site manager', 'Goods-in', 'Gatehouse']),
            'contact_phone' => fake()->numerify('+65-####-####'),
            'delivery_address' => fake()->randomElement([
                'Blk 512 Bedok North Ave 3, Singapore 469616 - rear gate, ask for Goods-in',
                'Jurong West Logistika, 1 Pioneer Road, Singapore - unit 12-04',
                '12 Jalan Kilang, Singapore - collection bay 3, quote the reference',
            ]),
        ]);
    }
}
