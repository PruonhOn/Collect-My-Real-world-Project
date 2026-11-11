<?php

namespace App\Enums;

/**
 * Where a shipment is in its lifecycle.
 *
 * This is a vocabulary, not a workflow. Nothing here validates that a shipment
 * moved from `booked` to `in_transit` rather than the reverse: a backdated
 * correction, a cancelled-then-restored consignment and a manual entry from a
 * paper bill of lading all make transitions that no state machine would allow,
 * and the operations team corrects records far more often than the machine could
 * predict. Enforcing an order would mean the real workflow would have to be
 * worked around, and the audit trail of a shipment is worth more than a tidy
 * state diagram.
 *
 * The order of the cases is the order a consignment normally moves through, so
 * a `values()` list reads as a progression in a dropdown.
 */
enum ShipmentStatus: string
{
    /** Being put together. May have no carrier, no vessel and no dates yet. */
    case Draft = 'draft';

    /** Space confirmed with the carrier. */
    case Booked = 'booked';

    /** Departed and moving. */
    case InTransit = 'in_transit';

    /**
     * Held at the border or destination for clearance.
     *
     * The clearance record itself belongs to the customs module, which owns it
     * along with the documents and duties. This status only says where the
     * consignment is, so a shipment list can show what is stuck.
     */
    case Customs = 'customs';

    /** At the destination port or terminal, not yet handed over. */
    case Arrived = 'arrived';

    /** Handed to the consignee. */
    case Delivered = 'delivered';

    /** Will not move. Kept rather than deleted: the reference may be re-used. */
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
            self::Draft => 'Draft',
            self::Booked => 'Booked',
            self::InTransit => 'In transit',
            self::Customs => 'At customs',
            self::Arrived => 'Arrived',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Whether the consignment has stopped moving.
     *
     * A closed shipment is one an operations dashboard counts as finished. It is
     * deliberately not the same as `Delivered`: a cancelled consignment is closed
     * but never arrived, and a delivered one is closed but was.
     */
    public function isClosed(): bool
    {
        return $this === self::Delivered || $this === self::Cancelled;
    }
}
