<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ContainerStatus;
use App\Http\Resources\ShipmentContainerResource;
use App\Models\ShipmentContainer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The container module: the equipment a consignment travels in.
 *
 * CRUD in full, and the route registration is unremarkable - `container` grants
 * view, create, update and delete in config/permissions.php, so unlike tracking
 * this module goes through the same `$module` helper as everything else. A box is
 * booked, moves, and is eventually given back, and somebody has to be able to take
 * a booking off the board when the space was released.
 *
 * What is specific to containers is that a number identifies an *asset* rather than
 * a document, so the uniqueness rule is a composite pair and not a single column.
 * See `rules()` below, and the migration for why a global index on
 * `container_number` would be wrong.
 */
class ContainerController extends RecordController
{
    /**
     * @return class-string<ShipmentContainer>
     */
    protected function modelClass(): string
    {
        return ShipmentContainer::class;
    }

    /**
     * @return class-string<ShipmentContainerResource>
     */
    protected function resourceClass(): string
    {
        return ShipmentContainerResource::class;
    }

    /**
     * The number an operator is reading off the box, the code stencilled beside it,
     * and the seal.
     *
     * `shipment_id` is absent on purpose, like TrackingController's: it is a column
     * this table has, and it is searched through the relation instead. Matching an
     * id against a `like` string would be a filter nobody can type correctly.
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return [
            ShipmentContainer::NUMBER,
            'iso_code',
            'type',
            'seal_number',
            'notes',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return [
            'id',
            ShipmentContainer::NUMBER,
            ShipmentContainer::STATUS,
            'created_at',
        ];
    }

    /**
     * Everything the resource reads, including the consignment's own reduced
     * fields. A row that lazy-loaded any of these would issue a query per row, and
     * `preventLazyLoading` is on outside production.
     *
     * @return array<int, string>
     */
    protected function eagerLoads(): array
    {
        return [
            'shipment',
            'shipment.carrier',
            'shipment.consignee',
            'shipment.portOfDischarge',
        ];
    }

    /**
     * The consignment is the thing being searched for. An operator working from a
     * bill of lading types the consignment reference, and searching this table's
     * own columns would never match it.
     *
     * Nested for the same reason ShipmentController nests its relation search:
     * appended straight onto the shared OR group, the first EXISTS binds to
     * whichever column preceded it and a match on one column starts requiring a
     * match on the one before it.
     *
     * @param  Builder<ShipmentContainer>  $inner
     */
    protected function applyRelationSearch(Builder $inner, string $search): void
    {
        $inner->orWhere(function (Builder $group) use ($search): void {
            $group->whereHas('shipment', fn (Builder $shipment): Builder => $shipment
                ->where('reference', 'like', '%'.$search.'%')
                ->orWhereHas('carrier', fn (Builder $carrier): Builder => $carrier
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')));
        });
    }

    /**
     * @param  Builder<ShipmentContainer>  $query
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        parent::applyFilters($query, $request);

        if ($status = $request->query(ShipmentContainer::STATUS)) {
            $query->where(ShipmentContainer::STATUS, $status);
        }

        if ($request->filled('shipment_id')) {
            $query->where('shipment_id', $request->integer('shipment_id'));
        }

        if ($request->filled('iso_code')) {
            $query->where('iso_code', $request->string('iso_code'));
        }

        // A carrier's gear department works its own fleet, so this is a first-class
        // filter rather than a search term.
        if ($request->filled('carrier_id')) {
            $query->whereHas('shipment.carrier', fn (Builder $carrier): Builder => $carrier
                ->where('carriers.id', $request->integer('carrier_id')));
        }

        /*
         * The worklist. Both narrow to consignments that are still moving, because a
         * delivered box is history and a damaged one that was written off after
         * delivery would sit on the list forever being nothing anybody can act on.
         */
        if ($request->boolean('needs_attention')) {
            $query->open()->needsAttention();
        }

        if ($request->boolean('open_only')) {
            $query->open();
        }

        if ($request->boolean('empty')) {
            $query->withStatus(ContainerStatus::Empty);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'shipment_id' => ['required', 'integer', Rule::exists('shipments', 'id')->whereNull('deleted_at')],
            'status' => ['sometimes', Rule::enum(ContainerStatus::class)],

            'container_number' => ['required', 'string', 'max:20', ...$this->uniqueForShipment($request, $model)],
            'iso_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'seal_number' => ['sometimes', 'nullable', 'string', 'max:50'],

            /*
             * Weights are unsigned and unbounded at the top. The rating on the door is
             * the constraint that matters and it is not something to enforce here: a
             * box is weighed by a third party on a weighbridge, and a 30,480kg rating
             * recorded as 31,200kg is a fact about the load, not a typo to reject. The
             * consequence is visible through `utilisation_percent` going negative,
             * which is the honest reading of an overload.
             *
             * Not cross-checked against each other either, for the same reason - the
             * arithmetic does not close on a real box and a partial update would
             * compare against a field the caller did not send.
             */
            'tare_weight_kg' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_gross_weight_kg' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'gross_weight_kg' => ['sometimes', 'nullable', 'integer', 'min:0'],

            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Uniqueness for a box on one consignment, rather than in the world.
     *
     * A container is a physical asset that goes round again, so a global index on
     * `container_number` would refuse the same box appearing on a later consignment
     * - a 422 telling the operator the container does not exist while it is standing
     * in the yard. The pair is what identifies the row.
     *
     * Scoped to the shipment from the request, or from the stored row when a PATCH
     * leaves it out. Without that fallback a PATCH that corrected only the seal
     * number would be checked against a null shipment and would pass, so a second
     * row for the same box on the same consignment could be created by sending the
     * pair one field at a time.
     *
     * @return array<int, mixed>
     */
    private function uniqueForShipment(Request $request, ?Model $model): array
    {
        $shipmentId = $request->exists('shipment_id')
            ? $request->input('shipment_id')
            : $model?->getAttribute('shipment_id');

        $rule = Rule::unique('shipment_containers', 'container_number')
            ->where('shipment_id', $shipmentId);

        if ($model !== null) {
            $rule = $rule->ignore($model->getKey());
        }

        return [$rule];
    }
}
