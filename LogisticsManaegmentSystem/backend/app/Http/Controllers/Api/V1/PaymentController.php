<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\ApiResponse;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The payment module: money received against an invoice.
 *
 * CRUD in full, through the same `$module` helper, and every one of the three
 * writes is wrapped in a transaction that resettles the invoice afterwards.
 *
 * This is the controller the whole finance design is arranged around. A payment
 * is not a record of its own - it is the thing that moves an invoice's balance, so
 * a payment written without the invoice's new balance and status would leave the
 * ledger contradicting itself. Wrapping the pair is not defensive coding; it is
 * the minimum that makes the two rows a single fact rather than two that happen
 * to have been written in the same request.
 */
class PaymentController extends RecordController
{
    /**
     * @return class-string<Payment>
     */
    protected function modelClass(): string
    {
        return Payment::class;
    }

    /**
     * @return class-string<PaymentResource>
     */
    protected function resourceClass(): string
    {
        return PaymentResource::class;
    }

    /**
     * The receipt number, the invoice it is against, and the bank reference.
     *
     * The bank reference is the unusual one and is the point: a clerk matching a
     * statement line pastes the narrative, and it is stored unindexed as free
     * text precisely because it has to be searchable. It is the only field that
     * can answer "was this really our money", and that is a search somebody will
     * run when a receipt is disputed.
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return [
            Payment::REFERENCE,
            'bank_reference',
        ];
    }

    /**
     * `paid_at` and not `created_at`, for the same reason `Payment::scopePaidBetween`
     * filters on it: a day's takings is cut by when the money arrived, and a
     * backdated receipt entered this afternoon still belongs to the day it was paid.
     *
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return [
            'id',
            Payment::REFERENCE,
            Payment::AMOUNT,
            Payment::METHOD,
            Payment::PAID_AT,
            'created_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function eagerLoads(): array
    {
        return [
            'invoice',
            'customer',
        ];
    }

    /**
     * The invoice number and the customer's name.
     *
     * A receipt list is read by tracking down one of three things: the invoice it
     * settles, the customer who paid, or the receipt number itself. The first two
     * are relations and the third is the reference above.
     *
     * @param  Builder<Payment>  $inner
     */
    protected function applyRelationSearch(Builder $inner, string $search): void
    {
        $inner->orWhereHas('invoice', fn (Builder $invoice): Builder => $invoice
            ->where('reference', 'like', '%'.$search.'%'))
            ->orWhereHas('customer', fn (Builder $customer): Builder => $customer
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('code', 'like', '%'.$search.'%'));
    }

    /**
     * @param  Builder<Payment>  $query
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        parent::applyFilters($query, $request);

        if ($method = $request->query(Payment::METHOD)) {
            $query->where(Payment::METHOD, $method);
        }

        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->integer('invoice_id'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        /*
         * The two worklists.
         *
         * `reconciliation` is the one with a person waiting on it: receipts whose
         * method means they carry a number that has to be matched by eye against a
         * bank statement, and which therefore cannot be trusted until somebody has
         * done that. It is derived from the method rather than stored, because it
         * is a property of how the money arrived and not a decision anybody made
         * about this row.
         *
         * `unreconciled` is the same set expressed as a filter, kept as a
         * distinct parameter so the query string reads as what it does.
         */
        if ($request->boolean('needs_reconciliation') || $request->boolean('unreconciled')) {
            $query->whereIn(Payment::METHOD, [
                PaymentMethod::BankTransfer->value,
                PaymentMethod::Cheque->value,
                PaymentMethod::LetterOfCredit->value,
            ]);
        }

        $this->applyWindow($query, $request, 'paid_from', '>=');
        $this->applyWindow($query, $request, 'paid_to', '<=');
    }

    public function store(Request $request): JsonResponse
    {
        $payment = DB::transaction(function () use ($request): Payment {
            $attributes = $this->withServerValues($this->validated($request, null), $request);

            $this->guardOverpayment($attributes);

            $payment = Payment::query()->create($attributes);

            $this->resettle($payment->invoice_id);

            return $payment;
        });

        return ApiResponse::created(
            $this->present($payment->refresh()),
            'Record created.',
            '/'.$request->path().'/'.$payment->getRouteKey(),
        );
    }

    public function update(Request $request, int|string $record): JsonResponse
    {
        $payment = DB::transaction(function () use ($request, $record): Payment {
            $payment = $this->find($request, $record);

            $attributes = $this->withServerValues($this->validated($request, $payment), $request);

            // Read before the fill, because after it the payment would be pointing at
            // the new invoice and the old one would be unreachable from here.
            $previousInvoiceId = $payment->invoice_id;

            // An amount or method change is a change of direction on the balance,
            // so a receipt becoming an adjustment has to be checked the same way a
            // new one is - including against the invoice's *current* balance, which
            // already has this payment in it.
            if ($this->changesAmountOrMethodOrInvoice($payment, $attributes)) {
                $this->guardOverpayment(
                    array_merge(['invoice_id' => $previousInvoiceId], $attributes),
                    $payment,
                );
            }

            $payment->fill($attributes)->save();

            $this->resettle($payment->invoice_id);

            /*
             * Moving a receipt between invoices is two facts changing, not one.
             *
             * The invoice it left keeps a `paid_amount` and a `balance` that
             * counted the money until this moment, and nothing else in the system
             * would ever recompute them - there is no hook on `invoice_id`, and
             * resettling only the destination would leave a second invoice reading
             * paid for money that has left it. An aged receivables report is exactly
             * the thing that gets relied on, so the figure nobody is looking at is
             * the last place to let it go stale.
             */
            if ($previousInvoiceId !== $payment->invoice_id) {
                $this->resettle($previousInvoiceId);
            }

            return $payment;
        });

        return ApiResponse::success(
            $this->present($payment->refresh()),
            'Record updated.',
        );
    }

    /**
     * Delete a receipt, which has to give the money back to the balance.
     *
     * The invoice is resettled rather than decremented, so the balance is again a
     * sum of what exists. Decrementing would be cheaper and would drift: a deleted
     * receipt that had itself been edited would leave a balance nobody can
     * reconstruct from the rows, and this is the exact case where somebody will
     * try.
     */
    public function destroy(Request $request, int|string $record): JsonResponse
    {
        DB::transaction(function () use ($request, $record): void {
            $payment = $this->find($request, $record);

            $invoice = $payment->invoice_id;

            $payment->delete();

            $this->resettle($invoice);
        });

        return ApiResponse::success(null, 'Record deleted.');
    }

    /**
     * Refuses a receipt that would overpay the invoice.
     *
     * An overpayment is not a validation error in the abstract - customers do send
     * an extra amount, and a bank can credit twice - but on a single invoice it
     * leaves a negative balance, which is money this business owes the customer,
     * and there is no screen for that in this module. So it is refused here with a
     * 422 naming the field, and the way to record an over-remittance is a credit
     * against a *different* invoice.
     *
     * Not applied when the method is not a receipt, because an `adjustment` is
     * money going the other way and the guard is about taking more than is owed.
     */
    private function guardOverpayment(array $attributes, ?Payment $existing = null): void
    {
        $amount = $attributes[Payment::AMOUNT] ?? null;

        if ($amount === null) {
            return;
        }

        $method = PaymentMethod::tryFrom((string) ($attributes[Payment::METHOD] ?? PaymentMethod::BankTransfer->value));

        if ($method === null || ! $method->isReceipt()) {
            return;
        }

        if (! Money::isPositive((string) $amount)) {
            return;
        }

        $invoiceId = (int) ($attributes['invoice_id'] ?? 0);

        if ($invoiceId === 0) {
            return;
        }

        $invoice = Invoice::query()->find($invoiceId);

        if ($invoice === null || $invoice->status === InvoiceStatus::Void) {
            return;
        }

        /*
         * The balance already includes the payment being edited, so on an update
         * its own contribution has to be taken back out before the sum is
         * compared - otherwise correcting a 500 to 900 on an invoice owing 700
         * would be rejected against a balance that still counted the 500.
         *
         * Only when it is staying put. A payment being moved to another invoice is
         * not in *this* invoice's balance at all, so adding its amount back would
         * grant an allowance that was never there - and since the guard's job is
         * to stop an invoice being overpaid, a guard that is too generous on the
         * one path nobody tests is worse than no guard.
         */
        $balance = (string) $invoice->balance;

        if ($existing !== null
            && $existing->isReceipt()
            && (int) $existing->invoice_id === $invoiceId) {
            $balance = Money::add($balance, (string) $existing->amount);
        }

        if (Money::greaterThan((string) $amount, $balance)) {
            throw ValidationException::withMessages([
                Payment::AMOUNT => 'This payment is more than the outstanding balance on the invoice.',
            ]);
        }
    }

    /**
     * Whether a write changes the balance, whichever invoice it lands on.
     *
     * A move counts, because arriving at a different invoice changes that
     * invoice's balance just as surely as changing the figure does. Leaving it out
     * would let a receipt be dragged onto an invoice that is already fully settled
     * and overpay it by the guard's own measure.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function changesAmountOrMethodOrInvoice(Payment $payment, array $attributes): bool
    {
        if (array_key_exists('invoice_id', $attributes)) {
            return (int) $attributes['invoice_id'] !== (int) $payment->invoice_id;
        }

        if (array_key_exists(Payment::AMOUNT, $attributes)) {
            return Money::compare((string) $attributes[Payment::AMOUNT], (string) $payment->amount) !== 0;
        }

        return array_key_exists(Payment::METHOD, $attributes)
            && (string) $attributes[Payment::METHOD] !== (string) $payment->method?->value;
    }

    /**
     * Rewrites the invoice's received, balance and status figures.
     *
     * A no-op for a missing invoice, and a deliberate one: `payments.invoice_id`
     * is `cascadeOnDelete`, so this cannot normally happen, and if the row is
     * genuinely gone there is nothing to settle and throwing would only turn a
     * deleted invoice into a 500 on the way out.
     */
    private function resettle(?int $invoiceId): void
    {
        if ($invoiceId === null) {
            return;
        }

        $invoice = Invoice::query()->find($invoiceId);

        if ($invoice === null) {
            return;
        }

        $invoice->settle()->save();
    }

    /**
     * A half-open bound on the payment timestamp.
     *
     * Same rule as `DeliveryController::applyWindow()` and for the same reason: a
     * bare `Y-m-d` has to go through `whereDate` or the closing day is compared
     * against midnight and silently dropped, which on a cash report means the
     * figure for the day nobody wants to argue about is the wrong one.
     *
     * @param  Builder<Payment>  $query
     */
    private function applyWindow(Builder $query, Request $request, string $parameter, string $operator): void
    {
        $value = $request->query($parameter);

        if ($value === null || $value === '') {
            return;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) === 1) {
            $query->whereDate(Payment::PAID_AT, $operator, $value);

            return;
        }

        $query->where(Payment::PAID_AT, $operator, $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function withServerValues(array $attributes, Request $request): array
    {
        /*
         * The payer is the invoice's customer, and never the client's.
         *
         * The column is a deliberate copy rather than a join, and that is exactly
         * why it must not be client-settable: a copy is a record of who paid *at the
         * time*, and a client that could set it would make a receipt's history
         * depend on a field nobody has to think about. `Payment::booted()` reads it
         * off the invoice; this only has to stop the request from overriding it.
         */
        unset($attributes['customer_id']);

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'invoice_id' => ['required', 'integer', Rule::exists('invoices', 'id')],

            /*
             * Ours and unique, for the same reason an invoice reference is: it
             * identifies the row, and a client's own bank reference does not - two
             * remittances from one customer in a day can carry the same narrative.
             */
            'reference' => ['required', 'string', 'max:30', ...$this->uniqueRules('reference', $model)],

            /*
             * Positive, always, and deliberately. The `numeric` rule would accept a
             * negative number and the range then rejects it - but the message names
             * the range, not the idea. A credit is `PaymentMethod::Adjustment`,
             * which is a *positive* amount that the model knows means "out": a
             * negative bank transfer is a confusing thing to find in a
             * reconciliation, and a receipt that is negative on its face reads as a
             * data error rather than as a refund.
             */
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.9999'],

            'method' => ['required', Rule::enum(PaymentMethod::class)],

            /*
             * `nullable` rather than defaulted in the rules, because the model
             * supplies "now" and a client that wants a different moment says so. A
             * rule-level default would make the value immutable in the response
             * while the model used something else, and the two would disagree.
             */
            'paid_at' => ['sometimes', 'nullable', 'date'],

            'bank_reference' => ['sometimes', 'nullable', 'string', 'max:120'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
