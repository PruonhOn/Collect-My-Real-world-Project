<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ChargeStatus;
use App\Enums\InvoiceStatus;
use App\Http\Resources\InvoiceResource;
use App\Models\Charge;
use App\Models\Invoice;
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
 * The invoice module: what the business has asked a customer for.
 *
 * CRUD in full, through the same `$module` helper, with the two lifecycle actions
 * the other finance controllers do not need: `issue` and `void`.
 *
 * `issue` exists because sending a bill is an act, and an act is not the same
 * thing as editing a record. Drafting an invoice is a private act - it is
 * somebody assembling a figure - whereas issuing one is a commitment the customer
 * can rely on, and it starts the due-date clock. Modelling that as an
 * `update` that happens to change `status` would make it indistinguishable from
 * correcting a due date, and would let it be reversed by an ordinary PATCH.
 */
class InvoiceController extends RecordController
{
    /**
     * @return class-string<Invoice>
     */
    protected function modelClass(): string
    {
        return Invoice::class;
    }

    /**
     * @return class-string<InvoiceResource>
     */
    protected function resourceClass(): string
    {
        return InvoiceResource::class;
    }

    /**
     * The document number and the customer it is for.
     *
     * The number is the thing a caller pastes in from an email, and the customer
     * name is the thing an operator types when they do not have the number. Both
     * are indexed columns, so neither turns the search into a scan.
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return [Invoice::REFERENCE];
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return [
            'id',
            Invoice::REFERENCE,
            Invoice::STATUS,
            'total',
            Invoice::BALANCE,
            'issue_date',
            'due_date',
            'created_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function eagerLoads(): array
    {
        return [
            'customer',
        ];
    }

    /**
     * The customer's name and code, joined rather than searched on the invoice.
     *
     * "Find me the bills for ACME" is asked far more often than anything in the
     * invoice's own columns, and `reference` alone would miss it.
     *
     * @param  Builder<Invoice>  $inner
     */
    protected function applyRelationSearch(Builder $inner, string $search): void
    {
        $inner->orWhereHas('customer', fn (Builder $customer): Builder => $customer
            ->where('name', 'like', '%'.$search.'%')
            ->orWhere('code', 'like', '%'.$search.'%'));
    }

    /**
     * @param  Builder<Invoice>  $query
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        parent::applyFilters($query, $request);

        $status = $request->query(Invoice::STATUS);

        /*
         * `overdue` and `collectable` are derived sets reached through the `status`
         * parameter, not columns - which is why the column comparison below has to
         * skip them. Applying both would AND a `status = 'overdue'` onto a scope
         * that has already chosen the rows, and the query would return nothing at
         * all: the literal string is not in the column, so it matches no row. That
         * is the failure this branch exists to avoid, and it is why the derived
         * values are named here rather than being left to the column filter.
         */
        $derived = ['overdue', 'collectable'];

        if ($status && ! in_array($status, $derived, true)) {
            $query->where(Invoice::STATUS, $status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        /*
         * The receivables worklists, and they are separate parameters because they
         * answer different questions and are reached from different screens.
         *
         * `outstanding` is the whole aging report - everything still owed,
         * whatever the due date says. `overdue` is the alarm: past due and still
         * owing, which is the only one worth waking somebody for. `collectable` is
         * the middle ground - issued and part-paid, excluding drafts and voids.
         *
         * `overdue` is also available as an explicit `status=overdue` alias
         * below, because a filter dropdown offering `issued`, `partially_paid`,
         * `paid` and then `overdue` reads as four statuses when it is three plus a
         * derived flag. A dropdown that implies an invoice *is* overdue is a
         * dropdown that lets somebody assume a stale answer.
         */
        if (in_array($status, $derived, true)) {
            $status === 'overdue' ? $query->overdue() : $query->collectable();
        }

        if ($request->boolean('outstanding')) {
            $query->collectable()->where(Invoice::BALANCE, '>', 0);
        }

        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        if ($request->boolean('unpaid')) {
            $query->collectable()->where(Invoice::BALANCE, '>', 0);
        }

        /*
         * A due-date window, on the *due* date rather than the issue date.
         *
         * An aging report is cut by when the money was meant to arrive, so a
         * window filter on `due_date` is what a collections screen asks for.
         * `whereDate` on both bounds and inclusive at the top, so a filter of
         * `due_to: 2026-09-30` includes the 30th - the same shape as
         * `ShipmentController`'s delivery window and for the same reason.
         */
        $this->applyDateWindow($query, $request, 'due_from', '>=');
        $this->applyDateWindow($query, $request, 'due_to', '<=');
    }

    public function store(Request $request): JsonResponse
    {
        $invoice = DB::transaction(function () use ($request): Invoice {
            $invoice = Invoice::query()->create(
                $this->withServerValues($this->validated($request, null), $request),
            );

            $linesChanged = $this->attachCharges($invoice, $request);

            // Always settled, even on create, so a stored invoice and one created
            // through a different path are identical. A new invoice has no payments,
            // so this is the totals only.
            $invoice->settle($linesChanged)->save();

            return $invoice;
        });

        return ApiResponse::created(
            $this->present($invoice->refresh()),
            'Record created.',
            '/'.$request->path().'/'.$invoice->getRouteKey(),
        );
    }

    public function update(Request $request, int|string $record): JsonResponse
    {
        $invoice = DB::transaction(function () use ($request, $record): Invoice {
            $invoice = $this->find($request, $record);

            $this->guardEditable($invoice);

            $invoice->fill(
                $this->withServerValues($this->validated($request, $invoice), $request),
            );

            $linesChanged = $this->attachCharges($invoice, $request);

            $invoice->settle($linesChanged)->save();

            return $invoice;
        });

        return ApiResponse::success(
            $this->present($invoice->refresh()),
            'Record updated.',
        );
    }

    /**
     * Delete a draft, and only a draft.
     *
     * An issued invoice is a document the customer is holding, so it is voided
     * rather than deleted - and the charge lines survive either way, becoming
     * drafts again. Deleting a draft must therefore detach its lines instead of
     * cascading, or a consignment's quoted work would disappear with it. The
     * migration's `nullOnDelete` handles the invoice side; this is the model side.
     */
    public function destroy(Request $request, int|string $record): JsonResponse
    {
        DB::transaction(function () use ($request, $record): void {
            $invoice = $this->find($request, $record);

            $this->guardEditable($invoice);

            /*
             * Detach through the model, not a bulk `update`.
             *
             * The `invoice_id => null` write is what the `nullOnDelete` constraint
             * would do anyway, so this looks redundant - but a bulk update bypasses
             * `Charge::booted()`, and that is where the status is derived from the
             * `invoice_id`. A raw update would leave the lines reading `issued` with
             * nothing to be issued on, so every one of them would be invisible on a
             * consignment's billed total and visible on its quote. Going through
             * `fill()->save()` per line is a few more queries on a path that
             * deletes a document, and it is the difference between the two figures
             * agreeing and not.
             */
            foreach ($invoice->charges()->get() as $charge) {
                $charge->fill(['invoice_id' => null])->save();
            }

            $invoice->delete();
        });

        return ApiResponse::success(null, 'Record deleted.');
    }

    /**
     * Send the bill. Draft to issued, once.
     *
     * Idempotent in the other direction only: issuing an already-issued invoice is
     * a 409 rather than a silent success, because the caller almost certainly
     * believes it did something and the due-date clock has already started. The
     * rule is that the status must be `draft` and the invoice must have a total -
     * sending a bill for nothing is the one mistake here that reaches a customer.
     *
     * The conflicts are `abort(409)` rather than a returned envelope, so they roll
     * the transaction back on the way out. `bootstrap/app.php` renders a
     * `HttpException`'s message verbatim for the API, so these reach the client as
     * the sentences written here rather than as a generic "conflict" string.
     */
    public function issue(Request $request, int|string $record): JsonResponse
    {
        $invoice = DB::transaction(function () use ($request, $record): Invoice {
            $invoice = $this->find($request, $record);

            if ($invoice->status === InvoiceStatus::Void) {
                abort(409, 'A void invoice cannot be issued.');
            }

            if ($invoice->status === InvoiceStatus::Paid) {
                abort(409, 'This invoice is already settled.');
            }

            if ($invoice->status !== InvoiceStatus::Draft) {
                abort(409, 'This invoice has already been issued.');
            }

            // Re-settle before sending rather than trusting the stored total, so
            // an invoice issued seconds after a line was detached cannot go out
            // carrying a figure that is already wrong.
            $invoice->settle();

            if (! Money::isPositive((string) $invoice->total)) {
                abort(409, 'An invoice cannot be issued with a zero total.');
            }

            $invoice->status = InvoiceStatus::Issued;

            // Stamped on first issue and not on every send, and never moved after.
            // An invoice is dated, not stamped: "issued on the 3rd" is the fact a
            // customer cites in a dispute, and overwriting it on a re-send would
            // restate the document.
            if ($invoice->issue_date === null) {
                $invoice->issue_date = now()->toDateString();
            }

            $invoice->save();

            return $invoice;
        });

        return ApiResponse::success(
            $this->present($invoice->refresh()),
            'Invoice issued.',
        );
    }

    /**
     * Withdraw the bill, whatever state it is in.
     *
     * The one lifecycle action that is allowed on a part-paid or issued invoice,
     * because a commercial write-off is a decision somebody has to be able to
     * make - a customer that has gone away, a consignment that was never really
     * ours. The money is not forgotten: the balance stays at whatever it was, so
     * the amount written off is visible on the row rather than being implied by
     * the status.
     */
    public function void(Request $request, int|string $record): JsonResponse
    {
        $invoice = DB::transaction(function () use ($request, $record): Invoice {
            $invoice = $this->find($request, $record);

            if ($invoice->status === InvoiceStatus::Void) {
                // Idempotent here rather than a 409, the opposite of `issue`.
                // Voiding twice is a double-click or a retried request, and both
                // of those have already achieved the caller's intent.
                return $invoice;
            }

            $invoice->status = InvoiceStatus::Void;
            $invoice->save();

            return $invoice;
        });

        return ApiResponse::success(
            $this->present($invoice->refresh()),
            'Invoice voided.',
        );
    }

    /**
     * Puts the charge ids on the invoice and un-puts the ones that left.
     *
     * `charge_ids` rather than a per-line `invoice_id`, because the caller is
     * editing a *document* - the set of lines on it - and asking it to enumerate
     * which lines are being removed in order to keep two fields in step would make
     * the "remove this one" case a full statement of the lines being kept.
     *
     * Only when the parameter is present. A PATCH that does not mention charges
     * must not detach every line on the invoice, and `array_key_exists` is the
     * only way to tell that apart from `charge_ids: []`, which is a deliberate
     * request to strip the invoice bare.
     *
     * Returns whether the set of lines changed, which is what tells `settle()`
     * whether the invoice's totals are the sum of its lines or were typed onto it
     * by hand. False for a request that did not mention charges - not because
     * nothing happened, but because nothing was asserted about the line set, so
     * the invoice keeps whichever kind of total it already had.
     */
    private function attachCharges(Invoice $invoice, Request $request): bool
    {
        if (! $request->has('charge_ids')) {
            return false;
        }

        $ids = $request->input('charge_ids');

        if (! is_array($ids)) {
            return false;
        }

        $ids = [];
        foreach ((array) $idsRaw as $id) {
            $ids[] = (int) $id;
        }
        $ids = array_values(array_filter($ids, function ($value) {
            return $value !== 0 && $value !== null;
        }));

        $this->guardLinesAreUnclaimed($invoice, $ids);

        /*
         * Take the lines that are leaving off one at a time, through the model,
         * for the reason spelled out at length in `destroy()`: `Charge::booted()`
         * derives `status` from `invoice_id` and a bulk `update` bypasses it. A
         * line un-billed this way would keep reading `issued` with nothing to be
         * issued on, which puts it on the consignment's billed total and not on
         * its quote - two figures that would then disagree with no way to tell
         * which is right.
         *
         * Only the ones actually leaving. A line that stays on this invoice is not
         * detached and re-attached, because that round trip would run it through
         * `draft` on the way and briefly make a live bill inconsistent with its
         * own lines.
         */
        foreach ($invoice->charges()->get() as $charge) {
            if (! in_array($charge->getKey(), $ids, true)) {
                $charge->fill(['invoice_id' => null])->save();
            }
        }

        if ($ids === []) {
            return true;
        }

        /*
         * The attach itself is a bulk `update` - it is a per-line status write as
         * well, and the status is set explicitly below rather than by the hook, so
         * nothing here depends on events that the query builder skips.
         *
         * `void` lines are excluded from the status change but still get the
         * `invoice_id`: a withdrawn line that is still attached to the bill it was
         * on is history somebody may need, and flipping it back to `issued` would
         * be a lie. The invoice's totals exclude it either way, because
         * `Invoice::recalculate()` sums only billable lines.
         */
        Charge::query()
            ->whereIn('id', $ids)
            ->update(['invoice_id' => $invoice->getKey()]);

        Charge::query()
            ->whereIn('id', $ids)
            ->where(Charge::STATUS, '!=', ChargeStatus::Void->value)
            ->update([Charge::STATUS => ChargeStatus::Issued->value]);

        return true;
    }

    /**
     * Refuses a line set that would take a charge off a bill somebody else holds.
     *
     * The `invoice_id` is the only record of which bill a line is on, so a request
     * naming a line that belongs to another invoice is asking to invoice the same
     * work twice. Letting it through would also leave the *other* invoice's
     * subtotal, tax, total and balance overstated, with nothing recomputing them:
     * the resettle chain only reaches invoices the write path knows about, and
     * this path only knows about one.
     *
     * A 422 on the field rather than a 403, for the same reason `guardEditable()`
     * uses one: the caller holds `invoice.update` and the conflict is about the
     * state of the records, not about who is asking. The fix - move the line off
     * the other invoice, or drop it from this request - is in the message.
     *
     * @param  array<int, int>  $ids
     */
    private function guardLinesAreUnclaimed(Invoice $invoice, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $taken = Charge::query()
            ->whereIn('id', $ids)
            ->whereNotNull('invoice_id')
            ->where('invoice_id', '!=', $invoice->getKey())
            ->exists();

        if (! $taken) {
            return;
        }

        throw ValidationException::withMessages([
            'charge_ids' => 'One or more of those charges is already on another invoice. Remove it from that invoice first.',
        ]);
    }

    /**
     * Refuses a write to an invoice whose money has already moved.
     */
    private function guardEditable(Invoice $invoice): void
    {
        if ($invoice->isEditable()) {
            return;
        }

        // A 422 with a field rather than a 403, because this is a conflict about
        // the *state* of the record rather than about who is asking: the caller
        // holds `invoice.update`, and the answer depends on what is on the row.
        throw ValidationException::withMessages([
            'status' => 'An invoice that has been issued cannot be edited. Void it instead.',
        ]);
    }

    /**
     * @param  Builder<Invoice>  $query
     */
    private function applyDateWindow(Builder $query, Request $request, string $parameter, string $operator): void
    {
        $value = $request->query($parameter);

        if ($value === null || $value === '') {
            return;
        }

        // `whereDate`, so the closing day is included in full. See the comment on
        // `DeliveryController::applyWindow()` - this is the same trap.
        $query->whereDate('due_date', $operator, $value);
    }

    /**
     * Drops the one validated field that is not a column.
     *
     * `charge_ids` is in the rules - it has to be, or the ids would never be
     * checked against the charges table - which means `Request::validate()`
     * returns it alongside the real columns, and `fill()` would then throw a
     * `MassAssignmentException` for a field that is not on the model at all.
     *
     * Every other module here can return the validated payload unchanged. This one
     * cannot, and the reason is a consequence of the `charge_ids` design: the
     * invoice is a document whose line set is being edited, and the line set is not
     * a column.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function withServerValues(array $attributes, Request $request): array
    {
        unset($attributes['charge_ids']);

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            /*
             * A customer, and only a customer. `PartyController` scopes the same
             * table three ways, and a shipper or a consignee is somebody on the
             * paperwork rather than the party being billed - an invoice raised
             * against a consignee is a real thing in freight, but it is raised with
             * the customer and the customer is who owes the money.
             */
            'customer_id' => ['required', 'integer', Rule::exists('parties', 'id')->whereNull('deleted_at')->where('type', 'customer')],

            'reference' => ['required', 'string', 'max:30', ...$this->uniqueRules('reference', $model)],

            /*
             * Two states, not five - and neither of them `issued`.
             *
             * `partially_paid` and `paid` are what the money says, written by
             * `Invoice::settle()`. Accepting them here would let a request mark a
             * 4,000 invoice paid with no payment row behind it, and a receivables
             * screen that disagrees with the bank is worse than one that has to
             * resolve a reference.
             *
             * `issued` is excluded for the same reason `issue()` is an action at
             * all: a PATCH carrying `status: issued` would send the bill while
             * skipping both things sending it is for - the re-settle and the
             * zero-total check - and would leave `issue_date` unstamped, so the one
             * fact a customer cites in a dispute is missing from the document.
             * It is also unreachable in practice: an issued invoice is refused by
             * `guardEditable()` before the rules are consulted, so the only state
             * this can move is a draft's.
             *
             * `void` is settable because it is a commercial decision, and unlike
             * `issued` it is not an act with guards attached that this path would
             * skip. On an issued invoice it is unreachable anyway - `guardEditable()`
             * refuses the PATCH first, and voiding a sent bill is what the `void`
             * action is for - so in practice this affords a draft the same outcome
             * as deleting it.
             */
            'status' => ['sometimes', Rule::enum(InvoiceStatus::class)->only([
                InvoiceStatus::Draft,
                InvoiceStatus::Void,
            ])],

            'issue_date' => ['sometimes', 'nullable', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],

            'currency' => ['sometimes', 'string', 'size:3'],

            /*
             * `charge_ids` is the set of lines on the bill, and it is validated as
             * a whole rather than per-line. Excluding lines already on *another*
             * invoice is the important half: a line cannot be billed twice, and the
             * `invoice_id` is the only place that fact is recorded, so allowing a
             * line to be attached to two bills would let a consignment be invoiced
             * for the same work twice with nothing to detect it.
             */
            'charge_ids' => ['sometimes', 'array'],
            'charge_ids.*' => ['integer', Rule::exists('charges', 'id')],

            /*
             * `subtotal`, `tax_amount`, `total`, `paid_amount` and `balance` are
             * all absent, and that is the mechanism by which they are server-owned.
             * `Request::validate()` returns only the keys its rules cover, so a
             * client sending `total` does not have it stored - the first three
             * follow the lines via `settle()` and the last two follow the
             * payments.
             */
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
