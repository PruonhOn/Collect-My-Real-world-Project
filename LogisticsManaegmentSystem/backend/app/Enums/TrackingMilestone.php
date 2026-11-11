<?php

namespace App\Enums;

/**
 * The last thing a carrier reported about a consignment.
 *
 * This is a report, not a decision. It is deliberately a separate vocabulary from
 * ShipmentStatus rather than a second copy of it, because the two answer different
 * questions and only one of them is ours:
 *
 * - `ShipmentStatus` is where *we* say the consignment is. It moves when an
 *   operator decides it has, and it is what the whole system plans around.
 * - `TrackingMilestone` is the last thing *they* told us. A carrier can report
 *   "gate in" while our own status still reads `booked`, because nobody has got
 *   round to confirming it, and that gap is exactly the thing an operations desk
 *   needs to see.
 *
 * Collapsing the two would mean a tracking screen that can only ever agree with
 * the shipment list, which is the one thing a tracking screen must not be.
 *
 * The order of the cases is the order a consignment normally reports through, so
 * a `values()` list reads as a progression in a dropdown.
 */
enum TrackingMilestone: string
{
    /** Space confirmed with the carrier. Cargo has not moved yet. */
    case Booked = 'booked';

    /** Cargo received at the origin terminal or depot. */
    case GateIn = 'gate_in';

    /** Left the loading point. */
    case Departed = 'departed';

    /** Moved to a different vessel, or off one vessel onto another. */
    case Transshipment = 'transshipment';

    /** At the discharge point, not yet cleared or delivered. */
    case Arrived = 'arrived';

    /** Held for customs clearance. */
    case CustomsHold = 'customs_hold';

    /** Released by customs. */
    case CustomsCleared = 'customs_cleared';

    /** Left the terminal for the consignee. */
    case OutForDelivery = 'out_for_delivery';

    /** Handed to the consignee. */
    case Delivered = 'delivered';

    /**
     * Something went wrong that the carrier volunteered rather than discovered.
     *
     * Rolling, a shortland, damage, a refused container. Not a status the
     * consignment is in - it is a report about the consignment, and the shipment
     * may well move again afterwards.
     */
    case Exception = 'exception';

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
            self::Booked => 'Booked',
            self::GateIn => 'Gate in',
            self::Departed => 'Departed',
            self::Transshipment => 'Transshipment',
            self::Arrived => 'Arrived',
            self::CustomsHold => 'Customs hold',
            self::CustomsCleared => 'Customs cleared',
            self::OutForDelivery => 'Out for delivery',
            self::Delivered => 'Delivered',
            self::Exception => 'Exception',
        };
    }

    /**
     * Whether this report is a problem somebody has to act on.
     *
     * The "needs attention" set. A consignment sitting at customs hold is not
     * moving and not broken, but it is not moving *because* of something, and an
     * exception is neither. Both belong on the same worklist; separating them
     * would mean an operator checking two filters to answer "is anything stuck".
     */
    public function isProblem(): bool
    {
        return $this === self::CustomsHold || $this === self::Exception;
    }

    /**
     * Whether nothing further should be expected.
     *
     * Only `delivered`. An `exception` is emphatically not terminal: the whole
     * point of reporting one is that the consignment is still going somewhere
     * once it is sorted out.
     */
    public function isFinal(): bool
    {
        return $this === self::Delivered;
    }
}
