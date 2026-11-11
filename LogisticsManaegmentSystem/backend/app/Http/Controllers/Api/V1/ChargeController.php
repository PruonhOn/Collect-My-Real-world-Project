<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ChargeStatus;
use App\Enums\ChargeType;
use App\Enums\InvoiceStatus;
use App\Http\Resources\ChargeResource;
use App\Models\Charge;
use App\Models\Invoice;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The charge module: priced lines raised against a consignment.
 *
 * CRUD in full, through the same `$module` helper as everything else, with one
 * difference: every write that touches an invoice is wrapped in a transaction and
 * resettles it afterwards.
 *
 * The reason is that a charge is not independent of the bill it is on. Moving a
 * line from one invoice to another - which is what changing `invoice_id` does -
 * has to change *both* invoices' totals, and if the second resettle failed the
 * first would have already been written. A charge list and an invoice total that
 * disagree is a figure nobody can defend, so the whole thing commits together or
 * not at all.
 */
class ChargeController extends RecordController
{
    /**
     * @return class-string<Charge>
     */
    protected function modelClass(): string
    {
        return Charge::class;
    }

    /**
     * @return class-string<ChargeResource>
     */
    protected function resourceClass(): string
    {
        return ChargeResource::class;
    }

    /**
     * The description and the consignment it is on.
     *
     * Not the notes: a line's notes are an internal aside about the work, and a
     * `q` matching them turns every typo into a silently short result. The
     * description is on the invoice the customer is holding, so it is the one free
     * text field that is genuinely a search target.
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return [Charge::REFERENCE];
    }

    /**
     * `total` is here rather than `unit_price` because a charge list is a money
     * list: somebody opening this module wants to know what has been quoted, and
     * sorting by the rate on a line says almost nothing about that.
     *
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return [
            'id',
            Charge::REFERENCE,
            Charge::STATUS,
            Charge::TYPE,
            'total',
            'created_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function eagerLoads(): array
    {
        return [
            'shipment',
            'invoice',
        ];
    }

    /**
     * The consignment is the other half of what somebody is searching for: an
     * operator quotes against a job, and a collections clerk is handed a
     * consignment reference rather than a line item.
     *
     * @param  Builder<Charge>  $inner
     */
    protected function applyRelationSearch(Builder $inner, string $search): void
    {
        $inner->orWhereHas('shipment', fn (Builder $shipment): Builder => $shipment
            ->where('reference', 'like', '%'.$search.'%'));
    }

    /**
     * @param  Builder<Charge>  $query
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        parent::applyFilters($query, $request);

        if ($status = $request->query(Charge::STATUS)) {
            $query->where(Charge::STATUS, $status);
        }

        if ($type = $request->query(Charge::TYPE)) {
            $query->where(Charge::TYPE, $type);
        }

        if ($request->filled('shipment_id')) {
            $query->where('shipment_id', $request->integer('shipment_id'));
        }

        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->integer('invoice_id'));
        }

        /*
         * The two lists a quotes screen is opened on, and they are the same
         * question at two stages of the job: work priced but not yet billed, and
         * work billed but not yet paid. Neither is a status equality - "not yet
         * billed" is a null reference, and "not yet paid" is the invoice's
         * balance - so they are scopes rather than `where status`.
         */
        if ($request->boolean('uninvoiced')) {
            $query->uninvoiced();
        }

        if ($request->boolean('unsettled')) {
            $query->whereHas(
                'invoice',
                fn (Builder $invoice): Builder => $invoice->where(Invoice::BALANCE, '>', 0),
            );
        }
    }

    public function store(Request $request): JsonResponse
    {
        $charge = DB::transaction(function () use ($request): Charge {
            $charge = Charge::query()->create(
                $this->withServerValues($this->validated($request, null), $request),
            );

            $this->resettle($charge->invoice_id);

            return $charge;
        });

        return ApiResponse::created(
            $this->present($charge->refresh()),
            'Record created.',
            '/'.$request->path().'/'.$charge->getRouteKey(),
        );
    }

    public function update(Request $request, int|string $record): JsonResponse
    {
        $charge = DB::transaction(function () use ($request, $record): Charge {
            $charge = $this->find($request, $record);

            // Read before the write, because moving a line off an invoice has to
            // resettle the invoice it *left* as well as the one it joined.
            $previousInvoice = $charge->invoice_id;

            $charge->fill(
                $this->withServerValues($this->validated($request, $charge), $request),
            )->save();

            $this->resettle($previousInvoice);
            $this->resettle($charge->invoice_id);

            return $charge;
        });

        return ApiResponse::success(
            $this->present($charge->refresh()),
            'Record updated.',
        );
    }

    public function destroy(Request $request, int|string $record): JsonResponse
    {
        DB::transaction(function () use ($request, $record): void {
            $charge = $this->find($request, $record);

            $invoice = $charge->invoice_id;

            $charge->delete();

            $this->resettle($invoice);
        });

        return ApiResponse::success(null, 'Record deleted.');
    }

    /**
     * Rewrites an invoice's totals, balance and status, if it is still there.
     *
     * A no-op for a null id, which is the common case - most charges are quoted
     * before they are billed, and there is no invoice to settle yet.
     *
     * Fails the transaction rather than swallowing the exception if the resettle
     * itself errors. A silently skipped resettle is the exact failure this whole
     * arrangement exists to prevent: the charge would be written, the invoice
     * would not, and the two would be permanently out of step with nothing in the
     * log.
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

        if ($invoice->status === InvoiceStatus::Void) {
            // A void invoice is a commercial decision and its figures are frozen:
            // recomputing them would quietly restate a write-off. This is the one
            // place the settle chain deliberately stops.
            return;
        }

        // `lineDerived` is always true from this controller, and that is not a
        // default being accepted: reaching here at all means a line was just
        // attached, detached, voided or deleted. The totals came from lines, so
        // they have to be rewritten even when the resulting set is empty - an
        // invoice whose last line has been removed must not keep billing for it.
        $invoice->settle(lineDerived: true)->save();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'shipment_id' => ['required', 'integer', Rule::exists('shipments', 'id')->whereNull('deleted_at')],
            'invoice_id' => ['sometimes', 'nullable', 'integer', Rule::exists('invoices', 'id')],

            /*
             * `void` is the only status a person may set.
             *
             * The other two are the invoice's business - `Charge::booted()` writes
             * them from whether a line is attached - so accepting them here would
             * let a request post `status: draft` alongside an `invoice_id` and
             * leave a bill showing a line that is not on it. `issued` is therefore
             * not settable either, and a client that wants to un-bill a line
             * detaches it.
             */
            'status' => ['sometimes', Rule::enum(ChargeStatus::class)->only([ChargeStatus::Void])],
            'type' => ['sometimes', Rule::enum(ChargeType::class)],

            'description' => ['required', 'string', 'max:255'],

            /*
             * Money and quantity as numeric ranges rather than as `Money::of`.
             *
             * `numeric` accepts what `Money::normalise` throws on, and the rule has
             * to run *after* normalisation so the comparison is against the
             * canonical decimal that is about to be stored - see
             * `RecordController::validated()`. A non-numeric amount is a 422 naming
             * the field, never a 500 and never a stored zero.
             *
             * `unit_price` is bounded below at zero and unbounded above on purpose:
             * a goodwill reduction is a negative line on a *charge*, and refusing
             * one here would push somebody into a contrived invoice-level discount
             * that could not be added up by hand. The refund direction is a payment
             * method, not a negative receipt.
             */
            'quantity' => ['sometimes', 'numeric', 'min:-999999', 'max:999999'],
            'unit_price' => ['sometimes', 'numeric', 'min:-999999999.9999', 'max:999999999.9999'],

            // A percentage, so 100 is the ceiling: a tax rate above that is a
            // decimal fraction typed into a field that wants a number.
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],

            'currency' => ['sometimes', 'string', 'size:3'],

            /*
             * The three derived totals are absent, and that is the whole mechanism
             * by which a client cannot set them - not an extra rule rejecting them.
             * `Request::validate()` returns only the keys its rules cover, so an
             * unlisted `subtotal`, `tax_amount` or `total` is dropped from the
             * payload before the model sees it, and the model's hook writes its own
             * figures.
             *
             * Worth being explicit that this means a request carrying a `total` is
             * *created*, at the server's figure, rather than refused. That is
             * deliberate: rejecting an unknown key would need a rule for every
             * non-field this API might grow, and a client that computed its own
             * total is better served by being told the right one in the response
             * than by a 422. What must not happen is the client's number being
             * stored, and that is what `ChargeTest` asserts.
             */
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
