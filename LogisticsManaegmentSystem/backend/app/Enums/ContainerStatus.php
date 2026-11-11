<?php

namespace App\Enums;

/**
 * Where a piece of equipment is in its journey with a consignment.
 *
 * A container is a physical asset rather than a document, which is the fact that
 * shapes this vocabulary. It has a state the carrier's gear-department tells us
 * about, and it is the same asset going round again on the next consignment - so
 * a status here describes a pairing, not the box.
 *
 * Deliberately separate from `TrackingMilestone`, for the same reason tracking is
 * separate from `ShipmentStatus`: the two answer different questions and only one
 * of them is ours. A carrier can tell us the box is on the water while our own
 * shipment status still reads `booked`, and that gap is worth seeing. But the
 * overlap is partial - `customs_hold` and `in_transit` appear in both - and the
 * duplication is the point rather than a smell. `TrackingMilestone` is the last
 * thing anybody reported; this is the state of a specific piece of steel, which can
 * be reported on while the consignment as a whole has not moved.
 *
 * The order of the cases is the order a box normally goes through, so `values()`
 * reads as a progression in a dropdown.
 */
enum ContainerStatus: string
{
    /** Picked up from the shipper, still empty, or returned to the depot. */
    case Empty = 'empty';

    /** Packed and sealed, on a chassis or at a terminal, not yet moving. */
    case Loaded = 'loaded';

    /** On the water or on the road between terminals. */
    case InTransit = 'in_transit';

    /** At a border or port, not permitted past customs. */
    case CustomsHold = 'customs_hold';

    /** Out of customs, available for collection or onward carriage. */
    case Released = 'released';

    /** Handed to the consignee. */
    case Delivered = 'delivered';

    /**
     * Written off or beyond repair, and it is not moving.
     *
     * Not a lifecycle stage: the box is out of service. It is here because a
     * damaged container stops a consignment just as effectively as a customs hold
     * does, and an operator filtering for "what is not moving" must see it.
     */
    case Damaged = 'damaged';

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
            self::Empty => 'Empty',
            self::Loaded => 'Loaded',
            self::InTransit => 'In transit',
            self::CustomsHold => 'Customs hold',
            self::Released => 'Released',
            self::Delivered => 'Delivered',
            self::Damaged => 'Damaged',
        };
    }

    /**
     * Whether this is a state somebody has to act on.
     *
     * The "needs attention" set, and it is the same pair the tracking worklist uses
     * for the same two reasons: a box sitting at customs is stuck without anybody
     * having revised a date, and a damaged box is broken rather than late.
     */
    public function needsAttention(): bool
    {
        return $this === self::CustomsHold || $this === self::Damaged;
    }

    /**
     * Whether nothing further should be expected of this box.
     *
     * Only `delivered`. A damaged box is emphatically not terminal - the consignment
     * it is on still has to go somewhere, quite possibly in a different one.
     */
    public function isFinal(): bool
    {
        return $this === self::Delivered;
    }
}
