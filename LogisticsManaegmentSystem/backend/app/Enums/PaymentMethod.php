<?php

namespace App\Enums;

/**
 * How money arrived.
 *
 * Recorded on the payment because "we have been paid" is not a complete answer to
 * a customer's next question, which is "how do we make sure it reached you, and
 * what reference do we put against it". A bank transfer arrives with a narrative
 * a credit control clerk has to match by eye; a card payment does not, and the
 * difference decides whether a payment can be posted the day it is entered or has
 * to wait for the statement.
 *
 * `letter_of_credit` is here because in this trade a documentary L/C is a routine
 * way to be paid, and it is not a variation of bank transfer: the payment is
 * conditional on documents the bank inspects, which is a different thing for a
 * finance officer to be reconciling.
 *
 * `adjustment` is the one that is not a way of paying. It covers a credit note, a
 * rounding correction, and a goodwill credit - money that comes back the other
 * way. It is its own case rather than a negative amount on a normal method
 * because a credit has to be visible in the day's takings as a credit, and a
 * negative bank transfer is a confusing thing to find in a reconciliation.
 */
enum PaymentMethod: string
{
    /** Paid into an account. Needs a reference to match against. */
    case BankTransfer = 'bank_transfer';

    /** Over the counter. */
    case Cash = 'cash';

    /** A card, in person or online. */
    case Card = 'card';

    /** A cheque, cleared or in the post. */
    case Cheque = 'cheque';

    /** A letter of credit, drawn against documents. */
    case LetterOfCredit = 'letter_of_credit';

    /** Money coming back: a credit note, a correction, a goodwill credit. */
    case Adjustment = 'adjustment';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Bank transfer',
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::Cheque => 'Cheque',
            self::LetterOfCredit => 'Letter of credit',
            self::Adjustment => 'Adjustment',
        };
    }

    /**
     * Whether this method moves money *towards* the invoice.
     *
     * An adjustment does not, which is why it is the one case where recording a
     * payment is allowed to make an invoice's balance go *up*. The controllers
     * check this before rejecting an overpayment, so a credit note is not turned
     * away as an impossible amount.
     */
    public function isReceipt(): bool
    {
        return $this !== self::Adjustment;
    }

    /**
     * Whether a payment by this method has to be matched to a bank statement
     * before it can be trusted.
     *
     * Cash and card are counted at the counter and are right by evening; a
     * transfer and a cheque arrive as a number in a narrative and can be posted
     * against the wrong invoice if nobody has read it.
     */
    public function needsReconciliation(): bool
    {
        return $this === self::BankTransfer || $this === self::Cheque || $this === self::LetterOfCredit;
    }
}
