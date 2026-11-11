<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Support\Money;
use Database\Factories\InvoiceFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * A demand for money: one customer's bill for work done.
 *
 * The centre of the finance module, and the only record here that is a document
 * rather than an entry. Everything else either produces a line on this or reduces
 * the balance on it.
 *
 * Three things about it are deliberate and worth stating before editing.
 *
 * 1. The totals are stored, and are the sum of the *stored* line totals rather
 *    than of quantity times unit price. An invoice is a document the customer is
 *    holding, so its total has to be the number that was on it when it went out.
 *    `Charge` freezes its own arithmetic for the same reason, and
 *    `recalculate()` re-derives this from those frozen figures - so correcting a
 *    line's description six months later cannot restate a settled invoice.
 *
 * 2. `paid_amount` and `balance` are server-owned. They are the net of the
 *    payments and the difference, written by `Payment` and never accepted from a
 *    request. Storing them is what lets the overdue worklist be an indexed
 *    comparison on two columns instead of a correlated subquery per row, and it is
 *    also what makes `status` derivable rather than settable.
 *
 *    "Net" is deliberate: a credit note is a payment row with an `adjustment`
 *    method, and it is subtracted rather than added, so `paid_amount` can be
 *    negative when more has been refunded than received. That is not a corrupt
 *    figure - it is money this business owes the customer, and it is what makes a
 *    credit visible on the invoice rather than only on the receipt. See
 *    `receivedTotal()`.
 *
 * 3. `status` has three states a person sets (`draft`, `issued`, `void`) and two
 *    it does not (`partially_paid`, `paid`). The derived two are what the money
 *    says, so writing them from a request would let a hand-set `paid` sit on an
 *    invoice with a balance outstanding - a receivables screen that disagrees
 *    with the bank is worse than one that has to resolve a reference.
 */
#[Fillable([
    'customer_id',
    'reference',
    'status',
    'issue_date',
    'due_date',
    'currency',
    'subtotal',
    'tax_amount',
    'total',
    'paid_amount',
    'balance',
    'notes',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    public const REFERENCE = 'reference';

    public const STATUS = 'status';

    public const BALANCE = 'balance';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::STATUS => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'total' => 'decimal:4',
            'paid_amount' => 'decimal:4',
            'balance' => 'decimal:4',
        ];
    }

    /**
     * A document number, not a word.
     *
     * Trimmed and upper-cased for the same reason a delivery reference is: a
     * number typed into an accounting package and one typed into this screen will
     * not agree on casing, and the unique index does not help with a
     * case-insensitive match. The prefix comes from `logistics.numbering.invoice`
     * but is supplied by the client, because the customer is quoting a number
     * their own system already issued.
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

        return $normalised;
    }

    protected static function booted(): void
    {
        /*
         * A payment changes the status without anybody asking it to.
         *
         * Ordered after the money is settled below, and reading the values that
         * save has just written rather than the relation, so the status can never
         * describe the previous state.
         *
         * This hook only ever moves a status *forward*, to `partially_paid` or
         * `paid`, and only when money has actually been received. It is the safety
         * net for a direct `save()` that never called `settle()`; the derivation
         * itself lives in `settle()`, which is the only thing that can move one
         * back. Keeping the two apart is what stops a stale `paid` surviving a
         * deleted receipt.
         */
        static::saving(function (self $invoice): void {
            $status = $invoice->status;

            if ($status === null || $status->isDerived() || $status === InvoiceStatus::Void) {
                return;
            }

            if (! Money::greaterThan((string) $invoice->paid_amount, '0')) {
                return;
            }

            $invoice->setAttribute(
                self::STATUS,
                Money::isZero((string) $invoice->balance)
                    ? InvoiceStatus::Paid->value
                    : InvoiceStatus::PartiallyPaid->value,
            );
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }

    /**
     * The lines on this bill.
     *
     * One-to-many and nullable at the far end: a charge belongs to exactly one
     * invoice, but an invoice does not have to have any. The monthly retainer and
     * the standing storage fee are billed straight onto an invoice as a figure,
     * with nothing raised against a consignment behind them.
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Rewrites the totals from the lines that are actually on this invoice.
     *
     * Sums the *stored* `subtotal`, `tax_amount` and `total` of the billable
     * charges rather than redoing the arithmetic, for the reason the totals are
     * stored at all - see the class docblock. A void line is excluded, and so is
     * anything that arrived with a void status from a race, so a withdrawn line
     * leaves a figure that can be added up by hand.
     *
     * The sum runs in `Money` and the collection is read with `->all()` rather
     * than lazily inside the loop, so this is one query and not one per row. The
     * caller is expected to be inside a transaction; see `settle()`.
     *
     * An invoice with no billable lines is left alone rather than zeroed, unless
     * `$lineDerived` says its totals came from lines. That flag is the whole reason
     * this method can be called safely from the payment path.
     *
     * The migration says an invoice can stand on its own - a monthly retainer, a
     * standing storage fee, a goodwill credit - with the totals typed onto it and no
     * consignment behind them. Summing an empty set would write 0.0000 over that
     * figure, so recording a payment against a retainer invoice would silently
     * destroy the amount being paid: the balance would come back negative and the
     * invoice would read as overpaid by its entire value. So the payment path calls
     * this with the flag off and the stored figures stand.
     *
     * The charge path calls it with the flag on. That is the opposite situation and
     * equally unambiguous: a line has just been detached, and an invoice whose
     * figures came from those lines has to lose them or it keeps billing for work
     * that is no longer on it. An invoice whose figures were typed by hand is not
     * in that position, and the caller knows which it is because it just wrote to
     * `charge_ids`.
     *
     * @param  bool  $lineDerived  whether the stored totals are the sum of the lines
     */
    public function recalculate(bool $lineDerived = false): self
    {
        $lines = $this->charges()->billable()->get(['subtotal', 'tax_amount', 'total']);

        if ($lines->isEmpty() && ! $lineDerived) {
            return $this;
        }

        $this->applyTotals(
            $this->sumOver($lines, 'subtotal'),
            $this->sumOver($lines, 'tax_amount'),
            $this->sumOver($lines, 'total'),
        );

        return $this;
    }

    /**
     * Sets the three demand-side totals and derives the balance.
     *
     * Separate from `recalculate()` because the two are needed at different
     * moments by different callers: the totals follow the lines, and the balance
     * follows the payments. Taking both from one method would mean a payment
     * could overwrite the line totals with zeros.
     *
     * @param  string|int|float|null  $subtotal
     * @param  string|int|float|null  $taxAmount
     * @param  string|int|float|null  $total
     */
    public function applyTotals(mixed $subtotal, mixed $taxAmount, mixed $total): self
    {
        $this->setAttribute('subtotal', Money::normalise($subtotal) ?? Money::zero());
        $this->setAttribute('tax_amount', Money::normalise($taxAmount) ?? Money::zero());
        $this->setAttribute('total', Money::normalise($total) ?? Money::zero());

        return $this;
    }

    /**
     * Rewrites the received and outstanding figures from the payments.
     *
     * The one place `paid_amount` and `balance` are written. Called inside the
     * payment transaction, so a balance is always the sum of payments that were
     * committed at the same time - never a half-finished figure from a payment
     * that was rolled back.
     */
    public function applyPaid(mixed $paidAmount): self
    {
        $paid = Money::normalise($paidAmount) ?? Money::zero();

        $this->setAttribute('paid_amount', $paid);
        $this->setAttribute(self::BALANCE, Money::subtract((string) $this->total, $paid));

        return $this;
    }

    /**
     * Recomputes the totals, the balance and the status, and saves.
     *
     * The single entry point every caller should use after touching either side of
     * the invoice, so there is exactly one order of operations. The status is set
     * explicitly here rather than left to `booted()` alone, because `booted()`
     * only ever moves a non-derived status *forward* to `partially_paid` or
     * `paid` and will not move one back when a payment is deleted - and deleting a
     * payment is a correction somebody has to be able to make.
     *
     * `$lineDerived` is passed through to `recalculate()` and defaults to false, so
     * the payment path - the only one that calls this without a reason to rewrite
     * the demand - leaves a hand-typed total alone. See `recalculate()` for why that
     * distinction is load-bearing.
     */
    public function settle(bool $lineDerived = false): self
    {
        $this->recalculate($lineDerived);

        $this->applyPaid($this->receivedTotal());

        $this->setAttribute(self::STATUS, $this->deriveStatus((string) $this->paid_amount)->value);

        return $this;
    }

    /**
     * Money received net of credits, as a signed figure.
     *
     * The sum honours each payment's *direction* rather than adding every amount,
     * and the direction is read from the method - not from the sign, because this
     * API never stores a negative payment.
     *
     * Summing the amounts as they stand would make an `adjustment` behave
     * identically to a bank transfer, which is exactly the confusion the method
     * exists to prevent: a credit note would *reduce* the balance while the row
     * said it was not a receipt, so the invoice would read as further paid at the
     * moment a refund was issued. Sign it instead, and the net is the honest
     * figure: what has genuinely come in, less what has genuinely gone back out.
     *
     * This is why `paid_amount` can legitimately be negative. That is not a
     * corrupt figure - it is money this business owes the customer, and it is what
     * makes the credit visible on the invoice rather than only on the receipt.
     */
    private function receivedTotal(): string
    {
        $total = Money::zero();

        foreach ($this->payments()->get(['amount', 'method']) as $payment) {
            $amount = (string) $payment->amount;

            $total = $payment->isReceipt()
                ? Money::add($total, $amount)
                : Money::subtract($total, $amount);
        }

        return $total;
    }

    /**
     * The status the money says the invoice is in.
     *
     * A void invoice is left alone - a write-off is a commercial decision, not
     * an arithmetic result, and an outstanding balance on a voided invoice is
     * exactly what it is supposed to be.
     *
     * The total is checked *before* the balance, and that ordering is the whole
     * subtlety here. "Balance is zero" means two quite different things depending
     * on whether anything was ever demanded: a 1,000 invoice paid in full, and a
     * brand-new invoice nobody has added a line to yet. Both have a zero balance
     * and they are not remotely the same state - calling the second `paid` would
     * put an empty draft on a paid report, and `issue()` refuses anything that is
     * not `draft`, so the bill could never be sent. So an invoice that has never
     * been worth anything stays where it is.
     *
     * This is also what keeps a stripped draft from reading as settled: detaching
     * its last line takes the total to zero, and the status has to go with it.
     *
     * A draft with money against it is promoted, because an operator who took a
     * payment without sending the bill has still issued it in every way that
     * matters to the ledger.
     */
    private function deriveStatus(string $paid): InvoiceStatus
    {
        $current = $this->status;

        if ($current === InvoiceStatus::Void) {
            return InvoiceStatus::Void;
        }

        $balance = (string) $this->balance;

        if (! Money::isPositive((string) $this->total)) {
            return $current === null ? InvoiceStatus::Draft : $current;
        }

        if (Money::isZero($balance)) {
            return InvoiceStatus::Paid;
        }

        if (Money::isPositive($paid)) {
            return InvoiceStatus::PartiallyPaid;
        }

        /*
         * Nothing received, and the status says otherwise.
         *
         * A *derived* status is not sticky, and this is the case that proves it. If
         * a `paid` invoice has a receipt deleted - a correction somebody has to be
         * able to make - then the money is no longer there, and leaving the status
         * reading `paid` would put a 1,000 receivable on a list of settled bills
         * and hide it from every overdue filter. So it falls back.
         *
         * To `issued` rather than `draft`, because the total is positive: something
         * was billed, and the only way to be here with money taken back off a
         * settled bill is that the bill had gone out. A draft is reached by the
         * branch above instead, where the total is zero.
         *
         * A *non-derived* status is sticky, which is the opposite rule and equally
         * deliberate: an `issued` invoice with nothing received is exactly what it
         * says, because the money has not moved at all.
         */
        if ($current !== null && $current->isDerived()) {
            return InvoiceStatus::Issued;
        }

        return $current === null ? InvoiceStatus::Draft : $current;
    }

    /**
     * The two figures a receivables screen asks for, as decimal strings.
     *
     * @param  Collection<int, Charge>  $lines
     */
    private function sumOver(Collection $lines, string $column): string
    {
        $amounts = [];
        foreach ($lines as $charge) {
            $amounts[] = (string) $charge->{$column};
        }

        return Money::sum($amounts);
    }

    /**
     * Whether anything is still owed on it.
     *
     * "Anything" is a strict positivity rather than "not settled", and the
     * difference is an overpayment: a customer who pays too much has a balance
     * below zero, which is money this business owes them. Treating that as settled
     * because the invoice status says `paid` would leave the credit invisible on
     * the one screen somebody would look for it.
     */
    public function isOutstanding(): bool
    {
        return Money::isPositive((string) $this->balance);
    }

    /**
     * Whether the money has been collected in full.
     *
     * A balance of zero, signed either way, plus a status that is not void. A
     * voided invoice with a zero balance is not "paid" - it was withdrawn.
     */
    public function isSettled(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    /**
     * Whether this is a live demand for money - the same set `scopeCollectable()`
     * selects, read from this row.
     *
     * Drafts have not been asked for and voids never were, so neither is something
     * anybody is owed. See `isOverdue()` for why this is a method rather than an
     * inline check in both places.
     */
    public function isCollectable(): bool
    {
        return ($this->status ?? InvoiceStatus::Draft)->isCollectable();
    }

    /**
     * Whether the due date has passed with money still outstanding.
     *
     * Derived from the clock and never stored, for the same reason
     * `Shipment::isLate()` is: a stored flag would need a scheduled job to flip it
     * and would be wrong for every invoice whose payment landed between runs. The
     * worklist filter, the row badge and the dashboard tile all call this, which
     * is what stops them disagreeing.
     *
     * A void invoice is never overdue, and neither is a draft: a draft has not
     * been asked for, so it has missed nothing. An invoice with no due date has
     * no deadline to have passed, and treating it as late from the day it was
     * created would put every open-ended retainer on the alarm list.
     *
     * The draft exclusion is `isCollectable()` rather than an inline status check,
     * so the method and `scopeOverdue()` - which filters through `collectable()` -
     * are reading one definition of "a live demand for money". Written separately
     * they would agree today and drift the first time a fourth collectable status
     * was added, and the failure would be a worklist row that then renders as not
     * overdue.
     *
     * `$now` is injectable for the same reason as everywhere else in this schema:
     * a test asks the question at a fixed moment rather than depending on what
     * time it is when the suite runs.
     */
    public function isOverdue(?DateTimeInterface $now = null): bool
    {
        if (! $this->isOutstanding() || ! $this->isCollectable()) {
            return false;
        }

        if ($this->due_date === null) {
            return false;
        }

        // The whole day, and not midnight: an invoice due today has not been
        // missed. Comparing against a date and casting to midnight would make
        // every invoice overdue for the whole of its due date.
        return $this->due_date->startOfDay() < ($now ?? now())->startOfDay();
    }

    /**
     * Whole days past the due date. Zero or negative while it is not late.
     *
     * Rounded through the date components rather than by dividing a millisecond
     * delta, so a daylight-saving shift cannot produce 0.97 days reading as 1.
     * This is the number a collections email is sorted by, and an invoice that
     * reads as 31 days late when it is 30 is the sort of thing that starts an
     * argument.
     */
    public function daysOverdue(?DateTimeInterface $now = null): int
    {
        if (! $this->isOverdue($now)) {
            return 0;
        }

        $today = ($now ?? now())->copy()->startOfDay();
        $due = $this->due_date->copy()->startOfDay();

        return (int) $due->diffInDays($today);
    }

    /**
     * Whether the invoice's total can still change.
     *
     * A thin wrapper over the enum, kept because the callers are controllers
     * asking a question about *this row* and `isEditable()` reads better at the
     * call site than an enum import.
     */
    public function isEditable(): bool
    {
        return ($this->status ?? InvoiceStatus::Draft)->isEditable();
    }

    /**
     * Only invoices that are a live demand for money.
     *
     * Excludes drafts, which have not been asked for, and void ones, which never
     * were. A receivables total that counted a draft would overstate what is owed
     * by whatever is sitting in somebody's drafts folder, which is the figure a
     * finance officer would be asked to defend and could not.
     *
     * @param  Builder<self>  $query
     */
    public function scopeCollectable(Builder $query): void
    {
        $query->whereIn(self::STATUS, [
            InvoiceStatus::Issued->value,
            InvoiceStatus::PartiallyPaid->value,
        ]);
    }

    /**
     * Collectable, and still owing something.
     *
     * The scope behind `isOutstanding()`, written as the same rule twice for the
     * reason `scopeOverdue()` is: a receivables total that counted a part-paid
     * invoice down to the penny would count it at zero, and the count and the
     * amount on the same card would then describe different sets of invoices.
     *
     * A balance below zero is excluded, which is an overpayment - money this
     * business owes the customer rather than the other way round, and it belongs
     * in a credit report rather than a receivables one.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOutstanding(Builder $query): void
    {
        $query->collectable()->where(self::BALANCE, '>', 0);
    }

    /**
     * Invoices that have actually been sent, whether or not they are settled.
     *
     * The set revenue is counted over, and the reason it is not `collectable()`:
     * an invoice stops being collectable the moment it is paid, and a revenue
     * figure that dropped paid invoices would fall every time a customer paid on
     * time - the opposite of what happened.
     *
     * Excludes drafts, which have not been asked for, and void ones, which never
     * were. Counting a void invoice as revenue would put a withdrawn bill into a
     * month's takings and nobody would find it again.
     *
     * @param  Builder<self>  $query
     */
    public function scopeRecognised(Builder $query): void
    {
        $query->whereNotIn(self::STATUS, [
            InvoiceStatus::Draft->value,
            InvoiceStatus::Void->value,
        ]);
    }

    /**
     * The alarm list: past due, still owing money.
     *
     * The scope and `isOverdue()` are the same rule written twice on purpose, and
     * the repetition is the point - a scope that quietly disagreed with the method
     * would put an invoice on a page that then said it was not overdue, which is
     * the failure this whole schema is arranged to prevent. The composition is
     * `collectable()` then a balance and date comparison, matching the method's
     * own exclusions: void, draft and undated invoices never appear.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOverdue(Builder $query, ?DateTimeInterface $now = null): void
    {
        $query->collectable()
            ->where(self::BALANCE, '>', 0)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', ($now ?? now())->toDateString());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWithStatus(Builder $query, InvoiceStatus $status): void
    {
        $query->where(self::STATUS, $status->value);
    }
}
