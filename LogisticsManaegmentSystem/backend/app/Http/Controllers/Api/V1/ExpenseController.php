<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ExpenseCategory;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The expense module: what the business paid out.
 *
 * CRUD in full through the same `$module` helper, and the plainest of the four
 * finance controllers - nothing here writes to anything else.
 *
 * That is not an oversight. A charge and a payment both move an invoice, and both
 * have to be written in a transaction with it. An expense moves nothing: it
 * records money already spent, against a consignment that may or may not have
 * been billed, and it never changes another record's figures. A consignment's
 * margin is computed by *reading* expenses at the moment a report is generated,
 * not by keeping a margin column in step with every write - which means a late
 * expense is reflected in the next report rather than being silently lost, and
 * there is no second copy of the truth to fall out of step.
 */
class ExpenseController extends RecordController
{
    /**
     * @return class-string<Expense>
     */
    protected function modelClass(): string
    {
        return Expense::class;
    }

    /**
     * @return class-string<ExpenseResource>
     */
    protected function resourceClass(): string
    {
        return ExpenseResource::class;
    }

    /**
     * Our reference and the supplier's name.
     *
     * Both are here because both are what somebody has in their hand when they
     * type: our `EXP-` number off an internal list, or a carrier's name off a
     * statement. `vendor_reference` is deliberately absent for the reason
     * `DeliveryController` excludes its failure reason - it is an external number
     * that repeats across a dozen lines of one statement, and searching it returns
     * a page of near-identical rows, which is a worse answer than none.
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return [
            Expense::REFERENCE,
            'vendor_name',
        ];
    }

    /**
     * `incurred_on` and not `created_at`, for the same reason every other finance
     * sort is on the business date: a month-end margin report is read by the month
     * the cost belongs to, not by the week somebody got round to keying it in.
     *
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return [
            'id',
            Expense::REFERENCE,
            Expense::CATEGORY,
            Expense::AMOUNT,
            Expense::INCURRED_ON,
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
            'booking',
            'carrier',
        ];
    }

    /**
     * The three things a cost hangs off, and the carrier's own name.
     *
     * A carrier's bill is the common case in this table and the report it feeds is
     * "what did this carrier cost us", so a name search that missed carriers would
     * make the module useless to the person who uses it most.
     *
     * @param  Builder<Expense>  $inner
     */
    protected function applyRelationSearch(Builder $inner, string $search): void
    {
        $inner->orWhereHas('shipment', fn (Builder $shipment): Builder => $shipment
            ->where('reference', 'like', '%'.$search.'%'))
            ->orWhereHas('carrier', fn (Builder $carrier): Builder => $carrier
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('code', 'like', '%'.$search.'%'));
    }

    /**
     * @param  Builder<Expense>  $query
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        parent::applyFilters($query, $request);

        if ($category = $request->query(Expense::CATEGORY)) {
            $query->where(Expense::CATEGORY, $category);
        }

        if ($request->filled('shipment_id')) {
            $query->where('shipment_id', $request->integer('shipment_id'));
        }

        if ($request->filled('booking_id')) {
            $query->where('booking_id', $request->integer('booking_id'));
        }

        if ($request->filled('carrier_id')) {
            $query->where('carrier_id', $request->integer('carrier_id'));
        }

        /*
         * The margin and spend figures, as distinct filter sets.
         *
         * This is the one place the two are surfaced as a choice, and it matters
         * because they are not the same number: `margin` excludes duty and tax,
         * which is money collected from a customer and remitted to an authority
         * rather than money the business spent, and `spend` includes it because a
         * total of what left the bank this month genuinely does. Defaulting either
         * one would make somebody reading the wrong figure be right by accident.
         */
        if ($request->boolean('margin_bearing') || $request->boolean('excludes_duty')) {
            $query->marginBearing();
        }

        if ($request->boolean('billable')) {
            $query->where('is_billable', true);
        }

        if ($request->boolean('unattributed')) {
            // The running costs of a business: no consignment behind them. A
            // question a finance officer asks monthly and cannot express as
            // `shipment_id = null`, because the general case is *none* of the
            // three references rather than one missing.
            $query->whereNull('shipment_id')
                ->whereNull('booking_id')
                ->whereNull('carrier_id');
        }

        $this->applyWindow($query, $request, 'incurred_from', '>=');
        $this->applyWindow($query, $request, 'incurred_to', '<=');
    }

    /**
     * A date window on the day the cost was incurred.
     *
     * `whereDate` on both bounds, inclusive at the top. The column is a `date` so
     * this is straightforward, but the closing-boundary mistake is the same one the
     * other window filters guard against and it would drop the last day of a month
     * from a month-end report - which is the one report this table exists to feed.
     *
     * @param  Builder<Expense>  $query
     */
    private function applyWindow(Builder $query, Request $request, string $parameter, string $operator): void
    {
        $value = $request->query($parameter);

        if ($value === null || $value === '') {
            return;
        }

        $query->whereDate(Expense::INCURRED_ON, $operator, $value);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'reference' => ['required', 'string', 'max:30', ...$this->uniqueRules('reference', $model)],
            'category' => ['required', Rule::enum(ExpenseCategory::class)],

            /*
             * All three optional and all three `nullable`.
             *
             * The general running costs of a forwarder - rent, a licence, a salary -
             * belong to no consignment, no booking and no carrier, and requiring any
             * of them would make it impossible to record the cost of running the
             * business at all. An expense with none of the three is a real row, and
             * `unattributed` exists above so it can be found.
             */
            'shipment_id' => ['sometimes', 'nullable', 'integer', Rule::exists('shipments', 'id')->whereNull('deleted_at')],
            'booking_id' => ['sometimes', 'nullable', 'integer', Rule::exists('bookings', 'id')->whereNull('deleted_at')],
            'carrier_id' => ['sometimes', 'nullable', 'integer', Rule::exists('carriers', 'id')->whereNull('deleted_at')],

            /*
             * The supplier's own number, and the supplier's name.
             *
             * `vendor_reference` is explicitly *not* unique, unlike ours above: a
             * monthly carrier statement carries the same number on a dozen lines,
             * and making it unique would mean inventing numbers to satisfy the
             * index. That is why the match against a statement line is a search
             * rather than a constraint.
             */
            'vendor_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'vendor_reference' => ['sometimes', 'nullable', 'string', 'max:120'],

            /*
             * Positive, as on a payment. A refund from a carrier is a receipt of a
             * different kind and there is no screen for it here, so a negative
             * amount would only ever be a data-entry error - and one that inflates
             * nothing, because every total is a sum. Rejecting it is clearer than
             * storing a number whose meaning depends on its sign.
             */
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.9999'],

            'currency' => ['sometimes', 'string', 'size:3'],

            // Defaults to today in the model rather than here; see the comment there.
            'incurred_on' => ['sometimes', 'nullable', 'date'],

            /*
             * A statement of intent, and validated as a boolean rather than
             * derived. `Expense::isBillable()` forces it false for duty and tax at
             * read time, because that is an accounting rule rather than something
             * the person keying the row could know.
             */
            'is_billable' => ['sometimes', 'boolean'],

            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
