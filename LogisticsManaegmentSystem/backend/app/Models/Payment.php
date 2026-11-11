<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Support\Money;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Money received against an invoice.
 *
 * The last link in the chain, and the only one that moves the balance. A payment
 * is not a balance in its own right: it is an amount plus a method plus a moment,
 * and the invoice's `paid_amount` is nothing more than the sum of these. Nothing
 * on the payment is authoritative and nothing may be edited to disagree with the
 * invoice - the direction of every write here is inward, towards the invoice.
 *
 * `customer_id` is copied from the invoice rather than joined through it, and
 * that is the one denormalised reference in this schema. "Receipts by customer
 * this month" is asked constantly and joining a parties table to answer it would
 * be a wasted round trip on the one report that gets run at month end. It is a
 * copy rather than a convenience because a payment is a historical fact about a
 * transaction: if the invoice's customer is later corrected, the receipt has to
 * stay where it was posted, or the month's takings would change retrospectively.
 *
 * No soft delete. A payment is a receipt for money that arrived, and a reversed
 * receipt is a *different* payment - a credit note with a `PaymentMethod::Adjustment`
 * and its own reference. Hiding the original would leave the month's cash
 * position unverifiable against a bank statement, which is the one thing a
 * receipt exists to let somebody do.
 */
#[Fillable([
    'invoice_id',
    'customer_id',
    'reference',
    'amount',
    'method',
    'paid_at',
    'bank_reference',
    'notes',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public const REFERENCE = 'reference';

    public const AMOUNT = 'amount';

    public const METHOD = 'method';

    public const PAID_AT = 'paid_at';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            self::METHOD => PaymentMethod::class,
            self::PAID_AT => 'datetime',
        ];
    }

    /**
     * The money fields, coerced before validation compares against them.
     *
     * `amount` for the same reason a charge's `quantity` is normalised - a
     * request can send a number or a string or an exponent and the column means
     * one of them - and `reference` upper-cased so a receipt number typed into an
     * accounting package and one typed here are the same row.
     *
     * `paid_at` is deliberately *not* normalised. A default is applied on save
     * instead: an operator recording a payment in the evening has not got a
     * timestamp to hand, and "when the money arrived" is a different question
     * from "when this was typed in" - so the default is now, and a backdated
     * payment is an explicit act rather than a forgotten field.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function normalise(array $attributes): array
    {
        $normalised = [];

        if (array_key_exists(self::REFERENCE, $attributes) && $attributes[self::REFERENCE] !== null) {
            $normalised[self::REFERENCE] = strtoupper(trim((string) $attributes[self::REFERENCE]));
        }

        if (array_key_exists(self::AMOUNT, $attributes)) {
            try {
                $amount = Money::normalise($attributes[self::AMOUNT]);
            } catch (InvalidArgumentException) {
                // Left for Laravel's own rule to reject with the field name, so
                // the client gets a 422 rather than a 500.
                return $normalised;
            }

            if ($amount !== null) {
                $normalised[self::AMOUNT] = $amount;
            }
        }

        return $normalised;
    }

    protected static function booted(): void
    {
        /*
         * When it happened, and who it was from.
         *
         * Both are defaults rather than requirements. The payer is the invoice's
         * customer, copied here so a receipt's history cannot be rewritten by a
         * later correction to the invoice - the whole reason the column is
         * denormalised - and the timestamp is now, because an operator entering a
         * payment at the end of the day has not got the bank's timestamp to hand
         * and inventing one would be worse than recording the entry time.
         */
        static::creating(function (self $payment): void {
            if ($payment->customer_id === null && $payment->invoice_id !== null) {
                $payment->setAttribute('customer_id', Invoice::query()
                    ->whereKey($payment->invoice_id)
                    ->value('customer_id'));
            }

            if ($payment->paid_at === null) {
                $payment->setAttribute(self::PAID_AT, now());
            }
        });

        /*
         * A receipt that moves invoices moves its payer with it.
         *
         * The copy is denormalised, so it can only be as trustworthy as the
         * invariant that keeps it in step - and a receipt moved onto a different
         * invoice would otherwise keep naming the customer of the *old* one. That
         * is the exact state the column exists to prevent, reached by the one
         * operation nobody thinks to check: a mis-keyed invoice reference is fixed
         * by pointing the receipt somewhere else, and the statement then says
         * somebody who is not the billed customer paid the bill.
         *
         * Correcting an amount or a note deliberately does not touch it - only a
         * change of invoice, since that is the only thing that changes who the money
         * is from.
         */
        static::updating(function (self $payment): void {
            if (! $payment->isDirty('invoice_id')) {
                return;
            }

            $payment->setAttribute('customer_id', Invoice::query()
                ->whereKey($payment->invoice_id)
                ->value('customer_id'));
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Who paid, copied at the time of posting.
     *
     * Nullable in the type system only for the width of the column: the foreign
     * key is not nullable, and the copy is made on create. It is declared
     * `belongsTo` rather than reached through the invoice so a resource that has
     * not loaded the relation does not trigger a query - `preventLazyLoading` is
     * on outside production, and a receipt list that raised once per row would be
     * unusable.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }

    /**
     * Whether this row reduced the balance, or put money back on the invoice.
     *
     * Asked of the method rather than the sign of the amount, because a negative
     * amount is not something this API accepts: a credit is an `adjustment` with
     * a positive amount and a method that means "out". Deciding from the method
     * keeps the one question - did this make the invoice more or less owed - from
     * depending on which of two encodings a caller happened to use.
     *
     * This is the only direction flag the row has, deliberately. It used to have a
     * second, `reducesBalance()`, which was this ANDed with the amount being
     * positive - and since the API refuses a non-positive amount, the two could
     * never disagree in practice. Two booleans that always agree is worse than
     * one: a client given the choice has to work out which is authoritative, and
     * the honest answer would have been "they are the same field".
     */
    public function isReceipt(): bool
    {
        return ($this->method ?? PaymentMethod::BankTransfer)->isReceipt();
    }

    /**
     * Whether it has to be matched to a bank statement before it can be trusted.
     *
     * A transfer, a cheque and a letter of credit all arrive as a number in a
     * narrative and can be posted against the wrong invoice if nobody has read
     * it. Cash and a card payment are counted at the counter and are right by
     * evening. The distinction decides whether a payment can be posted the day
     * it is entered or has to wait, and it is a property of the method so that
     * every screen asking the question gets the same answer.
     */
    public function needsReconciliation(): bool
    {
        return ($this->method ?? PaymentMethod::BankTransfer)->needsReconciliation();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWithMethod(Builder $query, PaymentMethod $method): void
    {
        $query->where(self::METHOD, $method->value);
    }

    /**
     * Payments in a window, on the day the money arrived.
     *
     * Filtered on `paid_at` rather than `created_at` for the reason the column is
     * a timestamp: a day's takings is cut by when the money landed, and a
     * backdated entry made this afternoon still belongs to the day it was paid.
     */
    public function scopePaidBetween(Builder $query, ?string $from, ?string $to): void
    {
        if ($from !== null && $from !== '') {
            $query->whereDate(self::PAID_AT, '>=', $from);
        }

        if ($to !== null && $to !== '') {
            $query->whereDate(self::PAID_AT, '<=', $to);
        }
    }
}
