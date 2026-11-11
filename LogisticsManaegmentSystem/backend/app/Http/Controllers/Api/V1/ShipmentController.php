<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Incoterm;
use App\Enums\PartyType;
use App\Enums\ShipmentStatus;
use App\Http\Controllers\Concerns\ValidatesPartyReferences;
use App\Http\Resources\ShipmentResource;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The shipments module.
 *
 * The first controller in the API that is not master data, and the reason the
 * shared behaviour was extracted into RecordController rather than inherited
 * from MasterDataController. The differences are the ones that matter: the search
 * runs across related parties and the carrier as well as the shipment's own
 * columns, there is no `is_active` to filter on, and the party references are
 * checked for kind rather than merely for existing.
 */
class ShipmentController extends RecordController
{
    use ValidatesPartyReferences;

    /**
     * @return class-string<Shipment>
     */
    protected function modelClass(): string
    {
        return Shipment::class;
    }

    /**
     * @return class-string<ShipmentResource>
     */
    protected function resourceClass(): string
    {
        return ShipmentResource::class;
    }

    /**
     * Searching only a shipment's own columns finds a consignment by reference
     * but not by customer, and the most common question about a consignment is
     * "what has this customer got on the water". So `q` also looks through the
     * parties and the carrier, via whereHas rather than a join: a join across
     * four tables to search one relation also multiplies the row count and
     * quietly breaks pagination.
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return [
            Shipment::REFERENCE,
            'cargo_description',
            'incoterm_place',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return [
            'id',
            'reference',
            'status',
            'etd',
            'eta',
            'weight_kg',
            'created_at',
        ];
    }

    /**
     * Every relation the resource reads, so a list row and a single record carry
     * the identical shape and `preventLazyLoading` never fires.
     *
     * @return array<int, string>
     */
    protected function eagerLoads(): array
    {
        return [
            'booking',
            'shipper',
            'consignee',
            'carrier',
            'vessel',
            'portOfLoading',
            'portOfDischarge',
            'originSite',
            'destinationSite',
            'truck',
            'driver',
        ];
    }

    /**
     * The `q` parameter reaches past the shipment's own columns.
     *
     * Searching only these three finds a consignment by reference but not by
     * customer, and the most common question about a consignment is "what has
     * this customer got on the water". So the parties and the carrier are included
     * too, via whereHas rather than a join: a join across four tables to search
     * one relation also multiplies the row count and quietly breaks pagination.
     *
     * Code as well as name on the related records, because that is what the
     * column search matches and an operator types both. Leaving the code out
     * makes `q=ACME` find nothing while the shipments screen shows ACME as the
     * shipper, which reads as a broken search rather than a narrow one.
     *
     * @param  Builder<Shipment>  $inner
     */
    protected function applyRelationSearch(Builder $inner, string $search): void
    {
        $nameOrCode = fn (Builder $related): Builder => $related
            ->where('name', 'like', '%'.$search.'%')
            ->orWhere('code', 'like', '%'.$search.'%');

        // Nested on purpose. Appended straight onto the shared OR group, the
        // first EXISTS binds to whichever column preceded it: SQL resolves `or a
        // like ? and exists(...)` as `or (a like ? and exists(...))`, so a
        // consignment matching on the shipper's name would silently also have to
        // match the column before it. A nested group gets its own parentheses, so
        // the precedence is the same whichever relation the caller searched.
        $inner->orWhere(function (Builder $group) use ($nameOrCode, $search): void {
            $group->whereHas('shipper', $nameOrCode)
                ->orWhereHas('consignee', $nameOrCode)
                ->orWhereHas('carrier', $nameOrCode)
                // A booking is keyed by a document number rather than a name, so
                // it needs its own match. Worth having: "what did we ship under
                // BKG-2026-0003" is asked often enough, and the booking reference
                // is what the operator has in front of them.
                ->orWhereHas('booking', fn (Builder $booking): Builder => $booking
                    ->where('reference', 'like', '%'.$search.'%'));
        });
    }

    /**
     * @param  Builder<Shipment>  $query
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        parent::applyFilters($query, $request);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // The counterpart to a status filter: the list an operations screen opens
        // with is "still moving", and requiring every caller to enumerate the
        // statuses that count as open would get it wrong the day a status is
        // added.
        if ($request->boolean('open_only')) {
            $query->open();
        }

        if ($request->boolean('unassigned')) {
            $query->unassigned();
        }

        if ($request->boolean('late')) {
            // Duplicated from the resource's is_late on purpose: the filter and
            // the flag a row then displays have to agree, and a caller listing
            // "late" and getting rows whose own is_late says false would be
            // looking at a bug, not a filter.
            $query->open()
                ->whereNotNull('eta')
                ->whereDate('eta', '<', now()->toDateString());
        }

        foreach (['carrier_id', 'shipper_id', 'consignee_id', 'vessel_id', 'truck_id', 'booking_id'] as $column) {
            if ($request->filled($column)) {
                $query->where($column, $request->integer($column));
            }
        }

        if ($date = $request->query('etd_from')) {
            $query->whereDate('etd', '>=', $date);
        }

        if ($date = $request->query('etd_to')) {
            $query->whereDate('etd', '<=', $date);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'reference' => ['required', 'string', 'max:30', ...$this->uniqueRules('reference', $model)],
            'status' => ['sometimes', 'string', Rule::enum(ShipmentStatus::class)],

            // Nullable on purpose: plenty of shipments are raised without a
            // booking behind them, from a paper bill of lading or because the
            // operator already held the space. Soft-deleted bookings are excluded
            // so the picker cannot offer one that is no longer live.
            'booking_id' => ['sometimes', 'nullable', 'integer', Rule::exists('bookings', 'id')->whereNull('deleted_at')],

            'shipper_id' => ['sometimes', 'nullable', $this->partyOfType(PartyType::Shipper)],
            'consignee_id' => ['sometimes', 'nullable', $this->partyOfType(PartyType::Consignee)],
            'carrier_id' => ['sometimes', 'nullable', 'integer', Rule::exists('carriers', 'id')->whereNull('deleted_at')],
            'vessel_id' => ['sometimes', 'nullable', 'integer', Rule::exists('vessels', 'id')->whereNull('deleted_at')],
            'port_of_loading_id' => ['sometimes', 'nullable', 'integer', Rule::exists('ports', 'id')->whereNull('deleted_at')],
            'port_of_discharge_id' => ['sometimes', 'nullable', 'integer', Rule::exists('ports', 'id')->whereNull('deleted_at')],
            'origin_site_id' => ['sometimes', 'nullable', 'integer', Rule::exists('sites', 'id')->whereNull('deleted_at')],
            'destination_site_id' => ['sometimes', 'nullable', 'integer', Rule::exists('sites', 'id')->whereNull('deleted_at')],
            'truck_id' => ['sometimes', 'nullable', 'integer', Rule::exists('trucks', 'id')->whereNull('deleted_at')],
            'driver_id' => ['sometimes', 'nullable', 'integer', Rule::exists('drivers', 'id')->whereNull('deleted_at')],

            'incoterm' => ['sometimes', 'nullable', Rule::enum(Incoterm::class)],
            'incoterm_place' => ['sometimes', 'nullable', 'string', 'max:120'],

            'cargo_description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pieces' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'weight_kg' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'volume_cbm' => ['sometimes', 'nullable', 'numeric', 'min:0'],

            'etd' => ['sometimes', 'nullable', 'date'],
            'eta' => $this->onOrAfter($request, $model, 'eta', 'etd'),
            'actual_departure' => ['sometimes', 'nullable', 'date'],
            'actual_arrival' => $this->onOrAfter($request, $model, 'actual_arrival', 'actual_departure'),

            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
