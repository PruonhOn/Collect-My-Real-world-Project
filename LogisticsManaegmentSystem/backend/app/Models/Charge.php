<?php

namespace App\Models;

use App\Enums\ChargeStatus;
use App\Enums\ChargeType;
use App\Support\Money;
use Database\Factories\ChargeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * One priced line on a customer's bill, raised against a consignment.
 *
 * The unit of the money the business earns. Four of these on a consignment and
 * an invoice is the shape of a normal freight job: ocean freight, documentation,
 * customs entry, and the trucking from the port to the door.
 *
 * The three totals are computed on save and stored, which is the decision that
 * shapes this whole model. The obvious alternative - leaving them out and having
 * the invoice add up `quantity * unit_price` on read - is cheaper and wrong,
 * because an issued invoice is a document the customer is holding. If somebody
 * corrects a line's description six months later and the description carried a
 * unit price in it, the total would move under an invoice that has already been
 * settled. Storing `subtotal`, `tax_amount` and `total` freezes the arithmetic at
 * the moment the line was saved, so `Invoice::recalculate()` re-derives the
 * invoice from the stored line totals and never from the rate card.
 *
 * All of it is `decimal(15,4)` arithmetic through `App\Support\Money`. Nothing
 * here touches a float, which is not a style choice: `0.0875 * 3` is exact in
 * decimal and `0.26249999999999996` in binary, and the second number is the one
 * that ends up on a customer's invoice.
 */
#[Fillable([
    'shipment_id',
    'invoice_id',
    'status',
    'type',
    'description',
    'quantity',
    'unit_price',
    'tax_rate',
    'subtotal',
    'tax_amount',
    'total',
    'currency',
    'notes',
])]
class Charge extends Model
{
    /** @use HasFactory<ChargeFactory> */
    use HasFactory;

    public const REFERENCE = 'description';

    public const STATUS = 'status';

    public const TYPE = 'type';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::STATUS => ChargeStatus::class,
            self::TYPE => ChargeType::class,
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'tax_rate' => 'decimal:2',
            'subtotal' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'total' => 'decimal:4',
        ];
    }

    /**
     * Normalises the money fields before validation compares against them.
     *
     * Same reason `Shipment::normalise` upper-cases a reference and for the same
     * reason it matters more here: a request can send `12.5` as a JSON number and
     * `"12.50"` as a string, and `1e3` as a third thing. The column is
     * `decimal(15,4)` and the rules compare ranges, so the value that is about to
     * be stored is the one that has to be compared. The three derived totals are
     * deliberately absent - they are the server's, and `booted()` writes them.
     *
     * A non-numeric amount throws rather than returning something to validate.
     * That surfaces as a 422 through the controller rather than a stored zero:
     * "the quantity was not a number" is a message somebody can act on, and a
     * charge that silently bills at 0 is not.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function normalise(array $attributes): array
    {
        $normalised = [];

        foreach (['quantity', 'unit_price', 'tax_rate'] as $column) {
            if (! array_key_exists($column, $attributes)) {
                continue;
            }

            try {
                $value = Money::normalise($attributes[$column]);
            } catch (InvalidArgumentException $exception) {
                // Left unmerged so Laravel's own rule produces the 422 with the
                // field name in it, rather than a 500 from here.
                continue;
            }

            if ($value !== null) {
                $normalised[$column] = $value;
            }
        }

        return $normalised;
    }

    protected static function booted(): void
    {
        /*
         * The totals, recomputed on every save.
         *
         * `creating` and `updating` rather than a single `saving`, because the
         * inputs and the outputs are being compared to each other: on a create
         * there is no stored total to disagree with, and on an update a total that
         * was edited by hand is precisely the thing that must be overwritten.
         *
         * Read from `getAttributes()` rather than through the casts, so this runs
         * against what is actually on the model and not against a value the cast
         * layer has already rounded into a float. The casts declare `decimal:4`,
         * which returns strings, and `Money` wants strings.
         */
        $recalculate = static function (self $charge): void {
            $raw = $charge->getAttributes();

            $quantity = Money::normalise($raw['quantity'] ?? '1') ?? Money::zero();
            $unitPrice = Money::normalise($raw['unit_price'] ?? '0') ?? Money::zero();
            $taxRate = Money::normalise($raw['tax_rate'] ?? '0') ?? Money::zero();

            $subtotal = Money::multiply($quantity, $unitPrice);
            $tax = Money::percentageOf($subtotal, $taxRate);

            $charge->setAttribute('subtotal', $subtotal);
            $charge->setAttribute('tax_amount', $tax);
            $charge->setAttribute('total', Money::add($subtotal, $tax));
        };

        static::creating($recalculate);
        static::updating($recalculate);

        /*
         * The status follows the invoice, and this is the only thing that ever
         * changes it.
         *
         * A charge that has been put on an invoice has been billed, whether or
         * not that invoice has gone out yet - the difference between `draft` and
         * `issued` is a property of the invoice, not of the line. So attaching
         * sets it and detaching clears it, and a form posting
         * `status: draft` alongside an `invoice_id` cannot leave a bill showing a
         * line that is not on it.
         *
         * `void` is excluded because it is a person's decision about the line and
         * detaching an invoice is not that decision. A voided charge is
         * withdrawn; the invoice it was on is a separate question, answered by
         * voiding or un-attaching that.
         */
        static::saving(function (self $charge): void {
            if ($charge->status === ChargeStatus::Void) {
                return;
            }

            $charge->setAttribute(
                self::STATUS,
                $charge->invoice_id === null ? ChargeStatus::Draft->value : ChargeStatus::Issued->value,
            );
        });
    }

    /**
     * The consignment this was raised for. Never null - a charge with nothing
     * behind it is not a charge.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * The invoice it is on, or null while it is still being quoted.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Who would be billed for it: the consignee of the consignment it is on.
     *
     * Denormalised onto the charge rather than only reachable through the
     * invoice, because the charge is raised long before any invoice exists and
     * "who have we quoted" is a question asked at quote stage. The invoice is
     * still the only place money is actually demanded from, and that is what
     * `Invoice::customer_id` decides - a customer's account manager can re-point
     * an invoice without rewriting a year of charges.
     */
    public function billTo(): ?Party
    {
        return $this->shipment?->consignee;
    }

    /**
     * Whether this line still counts towards what the customer will owe.
     *
     * A voided line does not, and a consignment's billed total is defined as the
     * sum over exactly the charges for which this is true - so the figure can be
     * checked by hand against the invoice.
     */
    public function isBillable(): bool
    {
        return ($this->status ?? ChargeStatus::Draft)->isBillable();
    }

    /**
     * Whether the line has been put on an invoice.
     *
     * Kept separate from `isBillable()` because they answer different questions
     * and both are asked: "has this been quoted up" is the operations question,
     * "does this count towards the total" is the finance one, and a void line is
     * not billable whether or not it was ever invoiced.
     */
    public function isInvoiced(): bool
    {
        return $this->invoice_id !== null;
    }

    /**
     * Whether the money behind this line has actually been collected.
     *
     * Asked of the invoice, not decided here. The resource ships the invoice's own
     * status for exactly this reason: a line that could hold a second copy of the
     * answer is a line that can contradict the invoice it sits on.
     */
    public function isSettled(): bool
    {
        return $this->invoice?->isSettled() ?? false;
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWithStatus(Builder $query, ChargeStatus $status): void
    {
        $query->where(self::STATUS, $status->value);
    }

    /**
     * Lines that count towards an invoice total.
     *
     * The one place in the schema where a scope is not simply "not finished":
     * `void` is excluded and `draft` is included, because a charge on a draft
     * invoice is already part of what that invoice says it is for. An invoice's
     * total is the sum over its billable lines, and this is that set.
     *
     * @param  Builder<self>  $query
     */
    public function scopeBillable(Builder $query): void
    {
        $query->where(self::STATUS, '!=', ChargeStatus::Void->value);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeUninvoiced(Builder $query): void
    {
        $query->whereNull('invoice_id');
    }
}
