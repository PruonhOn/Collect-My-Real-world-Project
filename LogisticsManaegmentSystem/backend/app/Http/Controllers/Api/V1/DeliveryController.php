<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DeliveryStatus;
use App\Http\Resources\ShipmentDeliveryResource;
use App\Models\ShipmentDelivery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The delivery module: the last leg, from the quay to the door.
 *
 * CRUD in full, through the same `$module` helper as everything else. A consignment
 * can have several of these and that is the design - a consignment delivered in
 * parts, or a failed attempt that was retried - so nothing here is keyed on the
 * consignment alone. The one key is `reference`, which is ours and globally unique,
 * the opposite of the composite keys the container and customs tables use. See the
 * migration for why the three differ.
 */
class DeliveryController extends RecordController
{
    /**
     * @return class-string<ShipmentDelivery>
     */
    protected function modelClass(): string
    {
        return ShipmentDelivery::class;
    }

    /**
     * @return class-string<ShipmentDeliveryResource>
     */
    protected function resourceClass(): string
    {
        return ShipmentDeliveryResource::class;
    }

    /**
     * The reference we issued, plus the three things somebody reads off a delivery
     * note: where the vehicle was going, who to ring, and the POD number.
     *
     * Not the failure reason, for the same reason CustomsController excludes its
     * hold reason: a delivery is not "found by searching what went wrong", and a `q`
     * matching free text turns every typo into a silently short result.
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return [
            ShipmentDelivery::REFERENCE,
            'delivery_address',
            'contact_name',
            'pod_reference',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return [
            'id',
            ShipmentDelivery::REFERENCE,
            ShipmentDelivery::STATUS,
            'scheduled_at',
            'delivered_at',
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
            'shipment.consignee',
            'truck',
            'driver',
        ];
    }

    /**
     * The consignment is the thing being searched for, and the vehicle is the other
     * one: a driver on the road is asked "where is that load", and a controller is
     * asked "what has this lorry done today".
     *
     * Nested for the same reason the other controllers nest theirs: appended
     * straight onto the shared OR group, the first EXISTS binds to whichever column
     * preceded it.
     *
     * @param  Builder<ShipmentDelivery>  $inner
     */
    protected function applyRelationSearch(Builder $inner, string $search): void
    {
        $inner->orWhere(function (Builder $group) use ($search): void {
            $group->whereHas('shipment', fn (Builder $shipment): Builder => $shipment
                ->where('reference', 'like', '%'.$search.'%')
                ->orWhereHas('consignee', fn (Builder $consignee): Builder => $consignee
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')))
                ->orWhereHas('truck', fn (Builder $truck): Builder => $truck
                    ->where('plate_number', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%'))
                ->orWhereHas('driver', fn (Builder $driver): Builder => $driver
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%'));
        });
    }

    /**
     * @param  Builder<ShipmentDelivery>  $query
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        parent::applyFilters($query, $request);

        if ($status = $request->query(ShipmentDelivery::STATUS)) {
            $query->where(ShipmentDelivery::STATUS, $status);
        }

        if ($request->filled('shipment_id')) {
            $query->where('shipment_id', $request->integer('shipment_id'));
        }

        if ($request->filled('truck_id')) {
            $query->where('truck_id', $request->integer('truck_id'));
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->integer('driver_id'));
        }

        /*
         * The two lists an operations desk actually opens the module on.
         *
         * Kept apart because they need opposite responses. `outstanding` is the
         * forward-looking plan - everything not handed over yet, including a failed
         * attempt, because the cargo is still in the truck. `overdue` is the alarm:
         * the slot has passed and nothing has happened, which is the only one of the
         * two worth waking somebody up for.
         */
        if ($request->boolean('outstanding')) {
            $query->open();
        }

        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        if ($request->boolean('failed')) {
            $query->withStatus(DeliveryStatus::Failed);
        }

        // Slots falling in a window rather than an equality, because "today's
        // deliveries" is the question the schedule screen actually asks and it cannot
        // be expressed with `=`. See applyWindow() for why the boundaries are not a
        // plain pair of comparisons.
        $this->applyWindow($query, $request, 'scheduled_from', '>=');
        $this->applyWindow($query, $request, 'scheduled_to', '<=');
    }

    /**
     * A half-open bound on a datetime filter, interpreted at day precision when the
     * caller gave a bare date.
     *
     * `where('scheduled_at', '<=', '2026-09-28')` looks like the obvious way to say
     * "up to the 28th" and it silently drops the 28th. The column is a timestamp, so
     * the string parses as midnight at the *start* of that day, and a delivery
     * booked for 10:00 on the 28th is after it. The filter would come back empty for
     * the one day anybody most wants to look at, and a "no deliveries today" reading
     * on a day that is fully booked is about the worst thing this screen can do.
     *
     * So the shape of the input decides the comparison: a bare `Y-m-d` is compared
     * with `whereDate`, which truncates the column to its date and so includes the
     * whole day, and anything with a time component is compared exactly. That also
     * matches `ShipmentController`, which uses `whereDate` throughout - the two
     * differ only because a shipment's ETD is a date and a delivery's slot is a time
     * of day.
     *
     * @param  Builder<ShipmentDelivery>  $query
     */
    private function applyWindow(Builder $query, Request $request, string $parameter, string $operator): void
    {
        $value = $request->query($parameter);

        if ($value === null || $value === '') {
            return;
        }

        // Strictly `Y-m-d`, so a full ISO timestamp is never mistaken for a day.
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) === 1) {
            $query->whereDate('scheduled_at', $operator, $value);

            return;
        }

        $query->where('scheduled_at', $operator, $value);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'shipment_id' => ['required', 'integer', Rule::exists('shipments', 'id')->whereNull('deleted_at')],
            'status' => ['sometimes', Rule::enum(DeliveryStatus::class)],

            /*
             * Ours and unique, so unlike the customs entry number it can be
             * normalised and indexed on: see the model. The DLV prefix comes from
             * `logistics.numbering.delivery` but is supplied by the client, exactly
             * as a shipment or booking reference is.
             */
            'reference' => ['required', 'string', 'max:30', ...$this->uniqueRules('reference', $model)],

            'truck_id' => ['sometimes', 'nullable', 'integer', Rule::exists('trucks', 'id')->whereNull('deleted_at')],
            'driver_id' => ['sometimes', 'nullable', 'integer', Rule::exists('drivers', 'id')->whereNull('deleted_at')],

            /*
             * Not cross-checked against each other, and that is deliberate twice over.
             *
             * Within one request the three timestamps are genuinely independent - a
             * delivery is booked in advance of the arrival it is collecting, and an
             * operator recording a late handover afterwards will not know what the
             * collection time was.
             *
             * Across a partial update it is structural: `after_or_equal:picked_up_at`
             * on `delivered_at` compares against a field the PATCH did not carry, and
             * a correction of one date cannot then be made without resending the
             * others. `RecordController::onOrAfter()` exists for the case where a
             * comparison is genuinely wanted, and this is not it.
             */
            'scheduled_at' => ['sometimes', 'nullable', 'date'],
            'picked_up_at' => ['sometimes', 'nullable', 'date'],
            'delivered_at' => ['sometimes', 'nullable', 'date'],

            'contact_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'delivery_address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'pod_reference' => ['sometimes', 'nullable', 'string', 'max:60'],
            'failure_reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
