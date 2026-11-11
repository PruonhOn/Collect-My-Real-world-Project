<?php

namespace App\Enums;

/**
 * Whether a charge is money we are intending to bill, or money we have billed.
 *
 * Only three states, and that is the whole design. The tempting fourth and fifth -
 * `partially_paid`, `overdue` - are deliberately not here, because they are
 * questions about the *invoice* a charge has landed on and not about the charge.
 * Recording them on the line would give the same money two answers that could be
 * set independently, and an accounts receivable screen that disagrees with itself
 * is worse than one that has to resolve a reference.
 *
 * So the rule is: this column says what a person did, and the invoice says what
 * the customer owes. A charge on a paid invoice is settled; the resource makes
 * that legible by shipping the invoice's own status alongside the charge rather
 * than by copying it onto the charge, which is why the two can never disagree.
 *
 * The case order is the order a charge travels: raised, put on a bill, withdrawn.
 */
enum ChargeStatus: string
{
    /**
     * Raised against a consignment, not yet on any invoice.
     *
     * Also where a charge sits between being added to an invoice and that invoice
     * being issued. The invoice's own `draft` status is what protects the customer
     * from seeing a total move underneath them, and a draft charge on a draft
     * invoice is that same protection viewed from the line.
     */
    case Draft = 'draft';

    /**
     * On an invoice the customer has been sent.
     *
     * The transition is the server's, not the client's: a charge becomes issued
     * when it is attached to an invoice and stops being issued when it is detached
     * from one, exactly as a party's `type` is decided by the route rather than
     * the payload. A form posting `status: draft` along with an `invoice_id`
     * cannot leave a bill showing a line that is not on it.
     */
    case Issued = 'issued';

    /**
     * Withdrawn.
     *
     * A state rather than a deletion, for the same reason a cancelled delivery is
     * one: a charge that was raised in error and then withdrawn is the answer to
     * "why is this line on this consignment", and a row that has been deleted
     * answers nothing. The invoice has to have been settled or voided first -
     * money that has been collected cannot be voided away by editing a line.
     */
    case Void = 'void';

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
            self::Issued => 'Issued',
            self::Void => 'Void',
        };
    }

    /**
     * Whether this charge still counts towards what the customer will owe.
     *
     * A draft does, because it is money the forwarder intends to bill and a
     * consignment's margin is real before the invoice goes out. A void charge is
     * not, and a consignment's billed total is defined as the sum over exactly
     * the charges for which this returns true - so a withdrawn line leaves a
     * figure that can be checked by hand against the invoice.
     */
    public function isBillable(): bool
    {
        return $this !== self::Void;
    }
}
