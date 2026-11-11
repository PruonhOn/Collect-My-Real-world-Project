<?php

namespace App\Enums;

/**
 * Where a consignment is in its last leg - off the quay and to the door.
 *
 * Its own vocabulary, like the customs and container ones, because the questions are
 * different: a consignment is either still moving between ports or it is being
 * driven somewhere, and a delivery record covers the second without touching the
 * first. A consignment that has been delivered may well still be awaiting its
 * invoice, and a delivery that failed says nothing about the consignment it was
 * carrying.
 *
 * The case order is the order a delivery actually goes through. Note that
 * `picking_up` is a distinct step from `in_transit` and is worth having as one: a
 * truck sent to collect a box from a terminal is on a different clock from a truck
 * already holding the cargo, and the two are the difference between a container
 * demurrage and a missed appointment. Collapsing them would leave an operator
 * unable to say whether the vehicle had gone anywhere yet.
 */
enum DeliveryStatus: string
{
    /** Booked with a carrier, with a slot, not yet collected. */
    case Scheduled = 'scheduled';

    /** The vehicle has gone to collect the cargo and has not picked it up. */
    case PickingUp = 'picking_up';

    /** The vehicle has the cargo and is on the road. */
    case InTransit = 'in_transit';

    /** Handed over at the agreed address. */
    case Delivered = 'delivered';

    /**
     * Attempted and could not be completed.
     *
     * Not a stage the consignment passes through - a failed attempt is a fact, and
     * the consignment is still sitting in the truck or back at the terminal. It is
     * its own status rather than a flag on `scheduled` because the reason is the
     * thing somebody has to read, and because a delivery can fail repeatedly.
     */
    case Failed = 'failed';

    /**
     * Called off.
     *
     * The consignment will go some other way - a different vehicle, a later slot, a
     * different address. Recorded rather than deleted because a cancellation is the
     * answer to "why did nothing turn up", and a booking that was quietly removed
     * from the system cannot answer it.
     */
    case Cancelled = 'cancelled';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * A label for the UI, where an underscore would read as a typo.
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::PickingUp => 'Picking up',
            self::InTransit => 'In transit',
            self::Delivered => 'Delivered',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Whether nothing further should be expected of this delivery.
     *
     * `delivered` and `cancelled`. Deliberately not `failed`: a failed attempt is
     * emphatically not the end, which is the whole reason the status is recorded
     * rather than the row being deleted - somebody has to go back and try again, and
     * the consignment is still in the vehicle.
     */
    public function isFinal(): bool
    {
        return $this === self::Delivered || $this === self::Cancelled;
    }

    /**
     * Whether the vehicle has the cargo and is on the road.
     *
     * The narrower of the two in-transit states, kept because "has it left yet" and
     * "is it on the road" are different questions with different consequences: a
     * collection that has not happened is a demurrage problem, and one that has is
     * an appointment problem.
     */
    public function isMoving(): bool
    {
        return $this === self::InTransit;
    }
}
