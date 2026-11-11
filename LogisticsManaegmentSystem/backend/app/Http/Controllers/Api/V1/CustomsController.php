<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CustomsStatus;
use App\Http\Resources\ShipmentCustomsClearanceResource;
use App\Models\ShipmentCustomsClearance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The customs module: where a consignment stands with the authority at one port.
 *
 * CRUD in full, through the same `$module` helper as everything else that grants
 * all four permissions. What is specific here is the key: a filing belongs to a
 * consignment *and* a port, and a transshipment has one row per port it cleared
 * at. The consequence that reaches into this controller is the uniqueness rule,
 * which is on the pair - see `rules()` below.
 *
 * The route name is `customs` because that is the permission namespace and the word
 * an operator uses, even though "customs" is the authority and what is recorded
 * here is one consignment's clearance with it.
 */
class CustomsController extends RecordController
{
    /**
     * @return class-string<ShipmentCustomsClearance>
     */
    protected function modelClass(): string
    {
        return ShipmentCustomsClearance::class;
    }

    /**
     * @return class-string<ShipmentCustomsClearanceResource>
     */
    protected function resourceClass(): string
    {
        return ShipmentCustomsClearanceResource::class;
    }

    /**
     * The reference the authority quotes back, and the broker's name.
     *
     * Not the hold reason or the notes: a consignment is not "found by searching
     * what customs said about it", and a `q` that matched free-text fields would
     * turn every typo into a silently short result.
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return [
            'entry_number',
            'broker',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return [
            'id',
            ShipmentCustomsClearance::STATUS,
            'submitted_at',
            'cleared_at',
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
            'shipment.carrier',
            'shipment.consignee',
            'port',
        ];
    }

    /**
     * The consignment is the thing being searched for. An operator chasing a held
     * filing has a consignment reference in front of them far more often than they
     * have the authority's entry number, and searching this table's own columns
     * would never match it.
     *
     * Nested for the same reason the other controllers nest theirs: appended
     * straight onto the shared OR group, the first EXISTS binds to whichever column
     * preceded it.
     *
     * @param  Builder<ShipmentCustomsClearance>  $inner
     */
    protected function applyRelationSearch(Builder $inner, string $search): void
    {
        $inner->orWhere(function (Builder $group) use ($search): void {
            $group->whereHas('shipment', fn (Builder $shipment): Builder => $shipment
                ->where('reference', 'like', '%'.$search.'%')
                ->orWhereHas('carrier', fn (Builder $carrier): Builder => $carrier
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')))
                ->orWhereHas('port', fn (Builder $port): Builder => $port
                    ->where('un_locode', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%'));
        });
    }

    /**
     * @param  Builder<ShipmentCustomsClearance>  $query
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        parent::applyFilters($query, $request);

        if ($status = $request->query(ShipmentCustomsClearance::STATUS)) {
            $query->where(ShipmentCustomsClearance::STATUS, $status);
        }

        if ($request->filled('shipment_id')) {
            $query->where('shipment_id', $request->integer('shipment_id'));
        }

        if ($request->filled('port_id')) {
            $query->where('port_id', $request->integer('port_id'));
        }

        /*
         * The two worklists, kept apart on purpose.
         *
         * `held` is the answer to "what is waiting on a signature" and includes a
         * filing that is merely in the queue, which is not a problem anybody has hit
         * yet. `needs_attention` is the answer to "what is wrong" and does not
         * include it. Folding them together would either put every routine
         * submission on the exception list or hide the refusals.
         */
        if ($request->boolean('held')) {
            $query->open()->held();
        }

        if ($request->boolean('needs_attention')) {
            $query->needsAttention();
        }

        if ($request->boolean('open_only')) {
            $query->open();
        }

        if ($request->filled('carrier_id')) {
            $query->whereHas('shipment.carrier', fn (Builder $carrier): Builder => $carrier
                ->where('carriers.id', $request->integer('carrier_id')));
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'shipment_id' => ['required', 'integer', Rule::exists('shipments', 'id')->whereNull('deleted_at')],

            /*
             * Nullable, and that is the one departure from the "a reference points at
             * something" rule: the column nulls on delete so a cargo manifest never
             * gains a hole. The flip side is that a filing can be created with no
             * port at all, which the unique pair below would otherwise not catch.
             */
            'port_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('ports', 'id')->whereNull('deleted_at'),
                ...$this->uniquePerPort($request, $model),
            ],
            'status' => ['sometimes', Rule::enum(CustomsStatus::class)],

            'entry_number' => ['sometimes', 'nullable', 'string', 'max:60'],
            'broker' => ['sometimes', 'nullable', 'string', 'max:120'],
            'submitted_at' => ['sometimes', 'nullable', 'date'],
            'cleared_at' => ['sometimes', 'nullable', 'date'],
            'hold_reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * One filing per consignment per port.
     *
     * A consignment that clears at two ports has two rows; a consignment that
     * clears twice at the same port is either a mistake or a re-presentation after a
     * refusal, and the refusal is supposed to stay on the record rather than be
     * overwritten. Which of those it is is not a question the validator can answer,
     * so the pair is enforced and the reasoning lives with the person filing.
     *
     * Scoped to the consignment from the request, or from the stored row when a
     * PATCH leaves it out - without that fallback a PATCH that corrected only the
     * status would be checked against a null consignment and would pass, so the pair
     * could be violated a field at a time.
     *
     * A null port is not caught here, and neither is it by the index: `port_id` is
     * nullable so a removed port cannot put a hole in a manifest, and a row with no
     * port is a filing somebody has not decided where to make yet. Two such rows on
     * one consignment are ambiguous rather than wrong, so they are left alone rather
     * than refused on a technicality.
     *
     * @return array<int, mixed>
     */
    private function uniquePerPort(Request $request, ?Model $model): array
    {
        if (! $request->filled('port_id')) {
            return [];
        }

        $shipmentId = $request->exists('shipment_id')
            ? $request->input('shipment_id')
            : $model?->getAttribute('shipment_id');

        $rule = Rule::unique('shipment_customs_clearances', 'port_id')
            ->where('shipment_id', $shipmentId);

        if ($model !== null) {
            $rule = $rule->ignore($model->getKey());
        }

        return [$rule];
    }
}
