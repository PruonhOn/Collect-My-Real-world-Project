<?php

namespace App\Enums;

/**
 * Where a demand for money stands.
 *
 * A finance officer opens the invoice screen with one question - who owes me money,
 * and how much of it is late - and this is the vocabulary that answers it. The
 * case order is the order an invoice travels, and the interesting boundary is
 * between `issued` and `overdue`.
 *
 * `overdue` is *not* a state an invoice is moved into. Nothing sets it, nothing
 * transitions into it, and there is no way to mark an invoice overdue by hand. It
 * is a fact about the clock: a due date passed with a balance still outstanding.
 * The same decision is already made by `Shipment::isLate()` and
 * `ShipmentDelivery::isOverdue()`, and this follows the rule those two set - the
 * derived state is computed where it is read, from the same expression the
 * worklist filter uses, so a figure on the dashboard and a row on the worklist
 * cannot disagree.
 *
 * `partially_paid` is derived the same way, from the payments recorded against
 * the invoice. It is in the enum rather than being a computed boolean so the
 * status vocabulary stays closed and the client renders one set of labels.
 */
enum InvoiceStatus: string
{
    /**
     * Being put together.
     *
     * The state that makes an invoice correctable. Once a number has been sent to
     * a customer, editing it retroactively is how a dispute starts, so a draft
     * can be edited freely and an issued one cannot have its lines or its total
     * rewritten. The way out is `void` and a replacement, which leaves both on
     * the record.
     */
    case Draft = 'draft';

    /**
     * Sent, and awaiting payment.
     *
     * The due date starts running here rather than at creation. An invoice
     * assembled over three days and sent on the fourth is not four days late on
     * the day it goes out.
     */
    case Issued = 'issued';

    /**
     * Some money received, some still owed.
     *
     * Derived from the payments rather than set, and the reason it is a case at
     * all rather than a badge over `issued`: a part-paid invoice is chased
     * differently from an untouched one, and a receivables list that showed both
     * as "Issued" would be hiding the row that is nearly closed behind the row
     * that has not started.
     */
    case PartiallyPaid = 'partially_paid';

    /** Settled in full. */
    case Paid = 'paid';

    /**
     * Withdrawn.
     *
     * For an invoice raised in error, or one the customer disputes and the
     * business writes off. A write-off is a real commercial event and it belongs
     * on the record - which is why this is a status on the invoice and not a
     * payment for the balance. Writing the balance off as a negative payment
     * would corrupt the money-in figure that a day's takings is summed from.
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
            self::PartiallyPaid => 'Partially paid',
            self::Paid => 'Paid',
            self::Void => 'Void',
        };
    }

    /**
     * Whether the status is arrived at by arithmetic rather than by a person.
     *
     * `partially_paid` and `paid` are what the payments say, not what an operator
     * chose. The controllers refuse to write either, which is what stops a
     * hand-set `paid` on an invoice with a balance standing.
     */
    public function isDerived(): bool
    {
        return $this === self::PartiallyPaid || $this === self::Paid;
    }

    /**
     * Whether nothing further should be expected.
     *
     * Settled or withdrawn. Not "past its due date" - that is `isOverdue()` on
     * the model, which is a different question about a different axis.
     */
    public function isFinal(): bool
    {
        return $this === self::Paid || $this === self::Void;
    }

    /**
     * Whether the invoice is a demand for money at all.
     *
     * A draft is not: it has not been asked for, so a receivables total that
     * counted it would overstate what is owed by whatever is sitting in someone's
     * drafts folder. A void invoice never was, and must not appear in any figure
     * even if its charges are still attached to it.
     */
    public function isCollectable(): bool
    {
        return $this === self::Issued || $this === self::PartiallyPaid;
    }

    /**
     * Whether the invoice's total can still change.
     *
     * True for a draft only. Once it has been issued the lines and the totals are
     * fixed, because the customer is holding a copy of the number. A charge
     * attached to an issued invoice is refused for the same reason.
     */
    public function isEditable(): bool
    {
        return $this === self::Draft;
    }
}
