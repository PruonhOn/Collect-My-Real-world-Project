<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Enums\TrackingMilestone;
use App\Models\Shipment;
use App\Models\ShipmentTracking;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<ShipmentTracking>
 */
class ShipmentTrackingFactory extends Factory
{
    protected $model = ShipmentTracking::class;

    /**
     * Every consignment gets one of these the moment it is created, so the default
     * state is the honest one: something was raised and nobody has reported on it
     * yet. A factory that defaulted to a plausible milestone would hide the case
     * the tracking screen exists to show.
     *
     * No `shipment_id` here, and that is not an oversight - see create() below.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'last_milestone' => null,
            'current_location' => null,
            'last_reported_at' => null,
            'revised_eta' => null,
            'delay_reason' => null,
            'source' => null,
            'notes' => null,
        ];
    }

    /**
     * Configures the tracking row a consignment already made, rather than adding
     * one.
     *
     * A tracking row cannot be inserted on its own: the consignment creates it in
     * Shipment::booted(), and the unique index on `shipment_id` is there to stop
     * anything else from trying. So the default here is to raise a consignment
     * and return the row that came with it, and a `shipment_id` passed to create()
     * configures the row belonging to that consignment instead.
     *
     * Overridden rather than left to the inherited insert because the inherited
     * one would try to add a second row for the same consignment and be refused by
     * the index - which is the correct refusal, and not something a test should
     * have to work around.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create($attributes = [], ?Model $parent = null)
    {
        $attributes = $this->state($attributes)->getExpandedAttributes($parent);

        $shipmentId = $attributes['shipment_id'] ?? null;
        unset($attributes['shipment_id']);

        $shipment = $shipmentId === null
            ? // In transit rather than a random status. A consignment that has
              // already been delivered or cancelled is not on any of the
              // worklists this factory builds the states for - the filters narrow
              // to cargo that is still moving, because a finished consignment going
              // quiet is history - so a random status would make half the states
              // here depend on luck.
              Shipment::factory()->create(['status' => ShipmentStatus::InTransit])
            : Shipment::query()->findOrFail($shipmentId);

        $tracking = $shipment->tracking;
        $tracking->fill($attributes);
        $tracking->save();

        return $tracking;
    }

    /**
     * Raised, and nothing has ever been reported on it.
     *
     * Already the default state, and named as well so a test can say which case it
     * is after it has stacked other states on top.
     */
    public function unreported(): static
    {
        return $this->state(fn (): array => [
            'last_milestone' => null,
            'current_location' => null,
            'last_reported_at' => null,
        ]);
    }

    public function milestone(TrackingMilestone $milestone): static
    {
        return $this->state(fn (): array => [
            'last_milestone' => $milestone,
            'last_reported_at' => now()->subHours(fake()->numberBetween(1, 48)),
        ]);
    }

    /**
     * A carrier report that arrived, and a location to go with it.
     *
     * The age range is derived from the staleness threshold rather than picked, and
     * stops a day short of it. A hand-written "1 to 72 hours" is the same number as
     * the three-day cutoff, and since the factory and the scope each ask the clock
     * separately, a row drawn at exactly 72 hours lands on the stale side by
     * microseconds - so a test asserting a report is current would pass or fail
     * depending on how fast the suite is running. Binding it to the constant means
     * a change to the policy moves this too, and the gap stays deliberate.
     */
    public function reported(?TrackingMilestone $milestone = null): static
    {
        return $this->state(fn (): array => [
            'last_milestone' => $milestone ?? fake()->randomElement(TrackingMilestone::cases()),
            'current_location' => fake()->randomElement([
                'Port of Singapore, terminal 3',
                'On the water, Indian Ocean',
                'Port of Long Beach, yard 12',
                'Singapore Freeport',
                'Customs office, Jurong',
            ]),
            'last_reported_at' => now()->subHours(fake()->numberBetween(1, ShipmentTracking::STALE_AFTER_DAYS * 24 - 24)),
            'source' => fake()->randomElement(['carrier email', 'EDI', 'phone call', 'carrier portal']),
        ]);
    }

    /**
     * Running behind, with a reason and a new date.
     *
     * Both set, because that is what a real delay report carries, and a delay
     * with a reason but no revised date is the case worth testing separately.
     */
    public function delayed(): static
    {
        return $this->reported()->state(fn (): array => [
            'delay_reason' => fake()->randomElement([
                'Rolled - vessel omitted this port',
                'Shortlanded at origin',
                'Customs examination extended',
                'Transshipment connection missed',
                'Weather hold',
            ]),
            'revised_eta' => now()->addDays(fake()->numberBetween(3, 21))->format('Y-m-d'),
        ]);
    }

    /**
     * The carrier revised the arrival but gave no reason.
     *
     * Kept as its own state because it still has to read as delayed: an operator
     * who revises a date is telling you the plan moved, whether or not they know
     * why.
     */
    public function revisedWithoutReason(): static
    {
        return $this->reported()->state(fn (): array => [
            'delay_reason' => null,
            'revised_eta' => now()->addDays(fake()->numberBetween(2, 14))->format('Y-m-d'),
        ]);
    }

    /**
     * Reported once and then gone quiet: the worklist this screen opens on.
     *
     * Offset from the threshold rather than given as an absolute range, for the
     * same reason `reported()` is: the first of these lands a day past the cutoff
     * so there is no ambiguity about which side of it a row sits on.
     */
    public function stale(): static
    {
        return $this->reported()->state(fn (): array => [
            'last_reported_at' => now()->subDays(ShipmentTracking::STALE_AFTER_DAYS + fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Held or broken, and needing somebody to look at it.
     */
    public function problem(): static
    {
        return $this->reported(
            fake()->randomElement([TrackingMilestone::CustomsHold, TrackingMilestone::Exception]),
        );
    }
}
