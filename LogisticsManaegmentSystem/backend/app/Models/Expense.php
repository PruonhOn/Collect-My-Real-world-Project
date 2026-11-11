<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use App\Support\Money;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Something the business paid for.
 *
 * The other side of the trade from `Charge`, and kept in its own table rather than
 * as a signed charge because the two are genuinely different: a charge is money we
 * have decided to bill, an expense is money that has already left. Collapsing them
 * into one table with a direction flag would make "what did this consignment cost
 * us" a sum over a column that also holds our revenue, and every margin figure
 * would then be a question about which rows somebody remembered to flip.
 *
 * Three optional references and a `category` do the work of organising it. A cost
 * can hang off a consignment, off the booking it was spent on, off the carrier
 * that billed for it, or off nothing at all - the rent, the licence, the salary -
 * and the general running costs of a forwarder are not attributable to any of
 * them, so a required reference would make it impossible to record the cost of
 * running the business.
 *
 * No soft delete, which is unusual here and for a good reason. The other
 * operational tables archive rather than delete because the *work* happened and
 * the record is a report about it. An expense is a payment, and a payment that
 * has been made cannot be un-made: deleting the row would remove the fact that
 * money left the business, which is the only thing this table exists to record.
 * A mistake is corrected by editing it, and a genuine non-expense is deleted
 * before anybody has counted it.
 */
#[Fillable([
    'reference',
    'category',
    'shipment_id',
    'booking_id',
    'carrier_id',
    'vendor_name',
    'vendor_reference',
    'amount',
    'currency',
    'incurred_on',
    'is_billable',
    'description',
    'notes',
])]
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    public const REFERENCE = 'reference';

    public const CATEGORY = 'category';

    public const AMOUNT = 'amount';

    public const INCURRED_ON = 'incurred_on';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::CATEGORY => ExpenseCategory::class,
            'amount' => 'decimal:4',
            self::INCURRED_ON => 'date',
            'is_billable' => 'boolean',
        ];
    }

    /**
     * The money field and our own number for it.
     *
     * `reference` upper-cased so a number keyed in from a statement and one typed
     * into this screen agree, and `amount` coerced for the same reason every other
     * amount in this schema is - a request can carry a number, a string or an
     * exponent, and the column means one canonical decimal.
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
                // Left for Laravel's own rule, so the client gets a 422 naming the
                // field rather than a 500.
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
        static::creating(function (self $expense): void {
            // An expense entered with no date is dated today. Backdating is an
            // explicit act, and a month-end margin report is read by the day the
            // cost belongs to rather than the day somebody got round to keying it.
            if ($expense->incurred_on === null) {
                $expense->setAttribute(self::INCURRED_ON, now()->toDateString());
            }
        });
    }

    /**
     * The consignment this was spent on, if it belongs to one.
     *
     * Nullable for the reason the column is: the running costs of a business are
     * not attributable to a consignment, and neither is a booking amendment fee
     * paid before any cargo was booked.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * The request for space this relates to.
     *
     * Separate from the consignment because a booking carries costs of its own -
     * a cancellation fee, an amendment, demurrage-free time bought up front - and
     * those exist before any cargo does. Attaching them to a shipment instead
     * would make them invisible on every booking that never became one.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * The carrier who billed for it.
     *
     * The relation that makes a consignment's true cost computable after the
     * fact: freight and demurrage are almost always somebody else's invoice, and
     * "what did this ship cost us" is a question about whose bills landed on it.
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    /**
     * Whether this cost counts against a consignment's margin.
     *
     * Duty and tax does not, and the reason is the reason it has its own category:
     * it is collected from the customer and remitted to the authority, so it is
     * money passing through rather than money spent. Counting it would make every
     * consignment look unprofitable by exactly the amount of the tax, which is
     * the kind of wrong that a manager notices and then stops trusting the whole
     * report over.
     */
    public function countsAgainstMargin(): bool
    {
        return ! ($this->category ?? ExpenseCategory::Other)->isHeldMoney();
    }

    /**
     * Whether this is money the business intends to push on to a customer.
     *
     * A statement of intent and nothing more: a terminal's detention fee is a
     * real cost and is usually recharged, and knowing which is which is how a
     * consignment's margin is worked out. It does not create a charge. What gets
     * recharged, and at what markup, is a decision somebody makes - and an
     * automatic charge would put a figure on an invoice that nobody agreed to
     * send.
     */
    public function isBillable(): bool
    {
        return (bool) $this->is_billable && $this->countsAgainstMargin();
    }

    /**
     * The amount as it should appear in a margin figure.
     *
     * Zero for held money rather than the amount itself, so a margin total can be
     * a plain sum over this expression without the caller having to re-apply the
     * category rule. Money is the only thing this returns, so a caller cannot
     * accidentally use it as a count.
     */
    public function marginAmount(): string
    {
        return $this->countsAgainstMargin() ? (string) $this->amount : Money::zero();
    }

    /**
     * The amount as it should appear in a total-spend figure.
     *
     * Unlike `marginAmount()` this includes held money, because "what did the
     * business pay out this month" genuinely does include the duty it remitted.
     * The two figures answer different questions and are deliberately not the same
     * number - a business that reports one as the other is reporting neither.
     */
    public function spendAmount(): string
    {
        return (string) $this->amount;
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWithCategory(Builder $query, ExpenseCategory $category): void
    {
        $query->where(self::CATEGORY, $category->value);
    }

    /**
     * Only the costs that count against a consignment's margin.
     *
     * The scope behind every margin figure, and the reason it exists rather than a
     * `whereNotIn` at each call site: "excluding duty and tax" is a rule about
     * money that must not be restated, because one call site that forgets produces
     * a margin report that is wrong in a way nobody can see.
     *
     * @param  Builder<self>  $query
     */
    public function scopeMarginBearing(Builder $query): void
    {
        $query->where(self::CATEGORY, '!=', ExpenseCategory::Duty->value);
    }

    /**
     * Costs falling in a window, on the day they were incurred.
     *
     * `whereDate` on both bounds, and inclusive at the top, for the same reason
     * `Shipment::late()` and the delivery window filters are: a `date` column
     * compared with a timestamp string would drop the closing day of the month and
     * make a margin report quietly miss it.
     *
     * @param  Builder<self>  $query
     */
    public function scopeIncurredBetween(Builder $query, ?string $from, ?string $to): void
    {
        if ($from !== null && $from !== '') {
            $query->whereDate(self::INCURRED_ON, '>=', $from);
        }

        if ($to !== null && $to !== '') {
            $query->whereDate(self::INCURRED_ON, '<=', $to);
        }
    }
}
