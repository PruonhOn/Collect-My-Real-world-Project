<?php

namespace App\Enums;

/**
 * Where a booking is in its lifecycle.
 *
 * A booking is the request for space, and the confirmation that came back. It
 * exists before the shipment does: an operator raises one against a shipper's
 * instruction, sends it to the carrier, and only once the carrier confirms is
 * there a consignment to track. That is the whole reason this is a separate
 * module from shipments rather than a status on them - the request has to be
 * recorded whether or not it ever turns into cargo that moves, and a shipment
 * with no booking is still a valid shipment.
 *
 * Like ShipmentStatus this is a vocabulary and not a workflow. Nothing here
 * refuses a `draft` that goes straight to `confirmed`, because that is exactly
 * what happens when the carrier confirms over the phone and the confirmation is
 * typed in afterwards. The order of the cases is the order a booking normally
 * moves through, so a `values()` list reads as a progression in a dropdown.
 */
enum BookingStatus: string
{
    /** Being put together. May have no carrier, no vessel and no dates yet. */
    case Draft = 'draft';

    /** Sent to the carrier, waiting on them to accept the space. */
    case Requested = 'requested';

    /** The carrier accepted. This is the point a shipment can be raised from. */
    case Confirmed = 'confirmed';

    /** Withdrawn before it was confirmed, or after, by either side. */
    case Cancelled = 'cancelled';

    /**
     * The carrier's confirmation window closed without an answer.
     *
     * Distinct from cancelled because nobody chose it: the request lapsed, and
     * the booking stays on file as the record of space that was asked for and
     * never got. Closing the window and letting a booking sit in `requested`
     * forever would leave the follow-up list indistinguishable from the backlog.
     */
    case Expired = 'expired';

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
            self::Requested => 'Requested',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
        };
    }

    /**
     * Whether the request is finished with, one way or another.
     *
     * Deliberately not the same as `Confirmed`: a confirmed booking is still open
     * work until the shipment it produced has been delivered, whereas a cancelled
     * or expired one will never produce anything. A dashboard counting "bookings
     * outstanding" wants everything that is not closed, and a dashboard counting
     * "requests lost" wants exactly these two.
     */
    public function isClosed(): bool
    {
        return $this === self::Cancelled || $this === self::Expired;
    }

    /**
     * Whether a shipment may be raised from a booking in this state.
     *
     * Not enforced as a validation rule anywhere, deliberately. The single place
     * it reads is the UI, and a correction that backdates a consignment onto a
     * booking the carrier never confirmed is a real thing that has to be
     * recordable - refusing it at the database would mean the correction could
     * not be entered at all, which is worse than an odd-looking link.
     */
    public function canProduceShipment(): bool
    {
        return $this === self::Confirmed;
    }
}
