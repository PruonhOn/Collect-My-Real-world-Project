<?php

namespace App\Enums;

/**
 * Where a consignment stands with the customs authority.
 *
 * The vocabulary of the authority, not of the operator. The status moves when the
 * authority says something, and the gaps between our statuses are the delays -
 * `submitted` to `approved` is where a consignment sits for a week on a
 * documentary check, and that week is exactly what an operations team is watching.
 *
 * Deliberately its own vocabulary rather than a slice of ShipmentStatus, for the
 * reason TrackingMilestone is: a consignment can read `customs_cleared` on our own
 * consignment record while this still says `under_review`, because two people filed
 * two reports and only one of them has been checked. Collapsing them would make a
 * customs screen that can only agree with the shipment list, which is the one thing
 * a customs screen must not be.
 *
 * `rejected` is not a stage the consignment leaves by going back to `pending` in
 * this system's terms. It is a decision by the authority against a filing, and what
 * happens next is normally a corrected filing - which is a new attempt recorded
 * elsewhere, not this row rewinding. The row is left as rejected so the reason is
 * still on the record when someone asks why it took a fortnight.
 *
 * The order of the cases is the order a clearance normally goes through.
 */
enum CustomsStatus: string
{
    /** Nothing has been filed yet. The state of every clearance the moment it is opened. */
    case Pending = 'pending';

    /** Filed with the authority and acknowledged. */
    case Submitted = 'submitted';

    /** With the authority, being looked at. */
    case UnderReview = 'under_review';

    /** Cleared for onward movement. */
    case Approved = 'approved';

    /**
     * Refused.
     *
     * Terminal for this filing, not for the consignment: the remedy is a corrected
     * filing rather than a container that has been turned around, and a consignment
     * is very often re-presented at the same port. That is a second row, and the
     * unique pair on (shipment, port) is what makes "the second attempt" a thing
     * this schema has to answer for rather than a conflict.
     */
    case Rejected = 'rejected';

    /**
     * The release has been carried out - duty assessed and paid, bond lodged, cargo
     * handed back.
     *
     * Separate from `approved` because the two are days apart in the common case
     * and the gap is where the detention charges start. A consignment that is
     * approved but not completed has been cleared on paper and is still sitting on
     * the quay, which is not a distinction anybody can afford to lose.
     */
    case Completed = 'completed';

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
            self::Pending => 'Pending',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Completed => 'Completed',
        };
    }

    /**
     * Whether nothing further should be expected of this filing.
     *
     * Only `completed`. `approved` is emphatically not terminal - the cargo is
     * cleared on paper and still has to be collected. `rejected` is terminal for
     * this filing but not for the consignment, and it is deliberately not counted
     * here: the row stays on the record as the reason the attempt failed, and
     * nothing about it is expected to change.
     */
    public function isFinal(): bool
    {
        return $this === self::Completed;
    }

    /**
     * Whether this is a state somebody has to act on.
     *
     * A rejection has to be chased, and the worklist is where the person chasing it
     * looks. Not `rejected` being terminal - the two questions are separate, and a
     * filing can be finished and still have been a problem worth remembering.
     */
    public function needsAttention(): bool
    {
        return $this === self::Rejected;
    }

    /**
     * Whether the cargo is still with the authority.
     *
     * Not the same as isFinal(), and the distinction is the point of the module: a
     * consignment that is `approved` is permitted to leave but has not, and an
     * operator looking for "what is still stuck at the border" needs the wider of
     * the two sets.
     */
    public function isHeld(): bool
    {
        return $this === self::Pending
            || $this === self::Submitted
            || $this === self::UnderReview;
    }
}
