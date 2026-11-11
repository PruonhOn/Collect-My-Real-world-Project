<?php

namespace Database\Factories;

use App\Enums\CustomsStatus;
use App\Models\Port;
use App\Models\Shipment;
use App\Models\ShipmentCustomsClearance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentCustomsClearance>
 */
class ShipmentCustomsClearanceFactory extends Factory
{
    protected $model = ShipmentCustomsClearance::class;

    /**
     * A filing that has not gone in yet, at a port of the consignment's own.
     *
     * `pending` with no entry number and no broker, because that is the true state
     * of the moment somebody opens a clearance record: nothing has been filed and
     * there is no reference to quote. A factory that started these mid-review would
     * hide the case the worklist exists to show.
     *
     * The port is a fresh one of its own rather than the consignment's discharge
     * port, and that is not laziness. Two records from this factory attached to one
     * consignment would collide on the unique (`shipment_id`, `port_id`) index, so a
     * test that wants the transshipment case has to be able to say which port each
     * filing is at - which is what `atPort()` is for, including naming the discharge
     * port when that is the point.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'port_id' => Port::factory(),
            'status' => CustomsStatus::Pending,
            'entry_number' => null,
            'broker' => null,
            'submitted_at' => null,
            'cleared_at' => null,
            'hold_reason' => null,
            'notes' => null,
        ];
    }

    /**
     * At a named port, for the records where which port is the whole point.
     */
    public function atPort(Port $port): static
    {
        return $this->state(fn (): array => [
            'port_id' => $port->id,
        ]);
    }

    /**
     * Filed with the authority, waiting on an acknowledgement.
     */
    public function submitted(): static
    {
        return $this->state(fn (): array => [
            'status' => CustomsStatus::Submitted,
            'entry_number' => fake()->numerify('####/####'),
            'broker' => fake()->randomElement(['Apex Customs Services', 'Meridian Freight Services', 'Kestrel Customs Brokers']),
            // A few hours back rather than "now", so a filing is never identical to
            // the instant it was written and a test that ages it has something to age.
            'submitted_at' => now()->subHours(fake()->numberBetween(2, 40)),
        ]);
    }

    /**
     * With the authority, being looked at.
     *
     * Days rather than hours, because this is where a consignment actually waits and
     * a review that finished the same afternoon is not the case being modelled.
     */
    public function underReview(): static
    {
        return $this->submitted()->state(fn (): array => [
            'status' => CustomsStatus::UnderReview,
            'submitted_at' => now()->subDays(fake()->numberBetween(2, 12)),
        ]);
    }

    /**
     * Cleared on paper. The cargo is permitted to leave but has not been collected,
     * which is the gap the quay charges live in.
     */
    public function approved(): static
    {
        return $this->underReview()->state(fn (): array => [
            'status' => CustomsStatus::Approved,
        ]);
    }

    /**
     * Refused, with the reason the reason is on the record.
     */
    public function rejected(): static
    {
        return $this->underReview()->state(fn (): array => [
            'status' => CustomsStatus::Rejected,
            'hold_reason' => fake()->randomElement([
                'Commercial invoice value understated',
                'Packing list does not match the manifest',
                'Certificate of origin not presented',
                'HS code inconsistent with the cargo description',
                'Licence required for this destination not held',
            ]),
        ]);
    }

    /**
     * The release carried out. Nothing further is expected of this filing.
     */
    public function completed(): static
    {
        return $this->approved()->state(fn (): array => [
            'status' => CustomsStatus::Completed,
            'cleared_at' => now()->subHours(fake()->numberBetween(1, 36)),
        ]);
    }

    /**
     * Goods taken off the quay for examination.
     *
     * A hold is not a rejection: the filing is alive and the cargo has been moved
     * somewhere indoors. Modelled as `under_review` with a reason, because that is
     * what it is, and because a separate "held" status would have to be reconciled
     * with the containers module's own hold.
     */
    public function examination(): static
    {
        return $this->underReview()->state(fn (): array => [
            'hold_reason' => fake()->randomElement([
                'Physical examination ordered at the terminal',
                'Held pending a second set of documents',
                'Valuation query raised by the authority',
            ]),
        ]);
    }
}
