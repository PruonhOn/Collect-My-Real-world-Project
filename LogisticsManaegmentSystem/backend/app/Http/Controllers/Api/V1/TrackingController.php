<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TrackingMilestone;
use App\Http\Resources\ShipmentTrackingResource;
use App\Models\Shipment;
use App\Models\ShipmentTracking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The tracking module: what we last heard about each consignment.
 *
 * The only module in the API with no create and no delete, and the controller is
 * shaped by that rather than by convenience. It extends RecordController for the
 * listing, the lookup and the update, and deliberately does not route `store` or
 * `destroy` at all - there is no permission to check and no rule to validate, so
 * a request to either path is a 405 from the router rather than a 403 from here.
 * The row exists because the consignment does; see Shipment::booted().
 *
 * That is also why there is no `shipment_id` in the rules below. The record
 * belongs to its consignment for the life of both, and `validated()` returns only
 * the keys the rules name, so a client that posts a different `shipment_id` has it
 * dropped rather than obeyed. It is called out here because it is the one field a
 * reader would expect to be settable.
 */
class TrackingController extends RecordController
{
    /**
     * @return class-string<ShipmentTracking>
     */
    protected function modelClass(): string
    {
        return ShipmentTracking::class;
    }

    /**
     * @return class-string<ShipmentTrackingResource>
     */
    protected function resourceClass(): string
    {
        return ShipmentTrackingResource::class;
    }

    /**
     * The free text an operator would plausibly type, plus the carrier's own
     * wording.
     *
     * The shipment reference is not here: it belongs to a column this table does
     * not have, so it is reached through `applyRelationSearch` below like every
     * other related field.
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return [
            'current_location',
            'delay_reason',
            'source',
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
            'last_milestone',
            'last_reported_at',
            'revised_eta',
            'created_at',
        ];
    }

    /**
     * Everything the resource reads, including the consignment's own reduced
     * fields. A worklist row that lazy-loaded any of these would issue a query per
     * row, and `preventLazyLoading` is on outside production.
     *
     * @return array<int, string>
     */
    protected function eagerLoads(): array
    {
        return [
            'shipment',
            'shipment.carrier',
            'shipment.shipper',
            'shipment.consignee',
            'shipment.portOfDischarge',
        ];
    }

    /**
     * The consignment is the thing being searched for. An operator who has a bill
     * of lading in front of them types the shipment reference, and searching this
     * table's own columns would never match it.
     *
     * Nested for the same reason ShipmentController nests its relation search:
     * appended straight onto the shared OR group, the first EXISTS binds to
     * whichever column preceded it and a match on one column starts requiring a
     * match on the one before it.
     *
     * @param  Builder<ShipmentTracking>  $inner
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
     * @param  Builder<ShipmentTracking>  $query
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        parent::applyFilters($query, $request);

        if ($milestone = $request->query('last_milestone')) {
            $query->where('last_milestone', $milestone);
        }

        /*
         * The two worklists. Both narrow to consignments that are still moving,
         * because a delivered consignment going quiet is history and a delivered
         * one being flagged as delayed is a record that will never change - they
         * would sit on the list forever being nothing anybody can do anything
         * about.
         */
        if ($request->boolean('delayed')) {
            $query->delayed()->open();
        }

        if ($request->boolean('stale')) {
            $query->open()->stale();
        }

        /*
         * The problem reports, kept separate from `delayed` because a consignment
         * held at customs is stuck without anybody having revised a single date.
         * Deriving it from the milestone rather than storing a flag keeps it
         * impossible for the two to disagree when a milestone is corrected.
         */
        if ($request->boolean('problems')) {
            $query->open()->whereIn('last_milestone', [
                TrackingMilestone::CustomsHold->value,
                TrackingMilestone::Exception->value,
            ]);
        }

        if ($request->filled('shipment_id')) {
            $query->where('shipment_id', $request->integer('shipment_id'));
        }

        // The consignment's carrier, which is the filter an operations desk
        // actually uses: "everything of theirs that has gone quiet".
        if ($request->filled('carrier_id')) {
            $query->whereHas('shipment.carrier', fn (Builder $carrier): Builder => $carrier
                ->where('carriers.id', $request->integer('carrier_id')));
        }
    }

    /**
     * Update only.
     *
     * There is no `shipment_id` rule and that is the design, not an oversight -
     * see the class docblock. Everything else is `sometimes` and nullable because
     * a PATCH carries only the fields being corrected, and because a report is
     * assembled from whatever the carrier happened to say: an operator who is told
     * the new arrival date usually cannot say where the cargo is.
     *
     * `revised_eta` is a plain date with no comparison against the shipment's ETA.
     * A revised date earlier than the plan is legitimate - a sailing that ran
     * ahead - and validating it would reject the good news.
     *
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'last_milestone' => ['sometimes', 'nullable', Rule::enum(TrackingMilestone::class)],
            'current_location' => ['sometimes', 'nullable', 'string', 'max:160'],
            'last_reported_at' => ['sometimes', 'nullable', 'date'],
            'revised_eta' => ['sometimes', 'nullable', 'date'],
            'delay_reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'source' => ['sometimes', 'nullable', 'string', 'max:50'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
