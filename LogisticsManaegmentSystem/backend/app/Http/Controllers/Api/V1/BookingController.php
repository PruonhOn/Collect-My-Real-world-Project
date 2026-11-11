<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\Incoterm;
use App\Enums\PartyType;
use App\Http\Controllers\Concerns\ValidatesPartyReferences;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The bookings module.
 *
 * The request for space and the confirmation that came back. Structurally this
 * is shipments again - the same partners, routing and cargo - and it inherits the
 * same shared behaviour, so the differences here are the ones that are actually
 * about bookings:
 *
 * - the lifecycle is about the *request* rather than the movement, so the useful
 *   filters are "still live" and "awaiting confirmation" rather than "late";
 * - the dates are `requested_etd`/`requested_eta` because what was asked for and
 *   what the carrier agreed are not always the same thing;
 * - there is no rule stopping a shipment being linked to a booking that was never
 *   confirmed. See BookingStatus::canProduceShipment() for why that is left to
 *   the person doing the correcting.
 */
class BookingController extends RecordController
{
    use ValidatesPartyReferences;

    /**
     * @return class-string<Booking>
     */
    protected function modelClass(): string
    {
        return Booking::class;
    }

    /**
     * @return class-string<BookingResource>
     */
    protected function resourceClass(): string
    {
        return BookingResource::class;
    }

    /**
     * The same three columns a shipment searches, for the same reason: the
     * question asked of a booking is "what has this shipper got booked", and the
     * reference is the document number the carrier quoted it by.
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return [
            Booking::REFERENCE,
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
            'requested_etd',
            'requested_eta',
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
            'shipper',
            'consignee',
            'carrier',
            'vessel',
            'portOfLoading',
            'portOfDischarge',
            'originSite',
            'destinationSite',
        ];
    }

    /**
     * The `q` parameter reaches past the booking's own columns to the parties and
     * the carrier, via whereHas rather than a join: a join across four tables to
     * search one relation also multiplies the row count and quietly breaks
     * pagination.
     *
     * Nested group for the precedence reason spelled out in ShipmentController -
     * appended straight onto the shared OR group, the first EXISTS binds to
     * whichever column preceded it.
     *
     * @param  Builder<Booking>  $inner
     */
    protected function applyRelationSearch(Builder $inner, string $search): void
    {
        $nameOrCode = fn (Builder $related): Builder => $related
            ->where('name', 'like', '%'.$search.'%')
            ->orWhere('code', 'like', '%'.$search.'%');

        $inner->orWhere(function (Builder $group) use ($nameOrCode): void {
            $group->whereHas('shipper', $nameOrCode)
                ->orWhereHas('consignee', $nameOrCode)
                ->orWhereHas('carrier', $nameOrCode);
        });
    }

    /**
     * @param  Builder<Booking>  $query
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        parent::applyFilters($query, $request);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // The list a bookings screen opens with: everything still live. Derived
        // from the statuses rather than enumerated, so it stays right the day a
        // status is added.
        if ($request->boolean('open_only')) {
            $query->open();
        }

        // The follow-up list. A booking in `requested` is somebody's outstanding
        // task, and on an unfiltered list it sits among the drafts nobody has sent
        // yet - which is why it gets its own filter rather than being left for
        // the caller to enumerate statuses.
        if ($request->boolean('pending')) {
            $query->awaitingConfirmation();
        }

        if ($request->boolean('unassigned')) {
            $query->unassigned();
        }

        foreach (['carrier_id', 'shipper_id', 'consignee_id', 'vessel_id'] as $column) {
            if ($request->filled($column)) {
                $query->where($column, $request->integer($column));
            }
        }

        if ($date = $request->query('requested_etd_from')) {
            $query->whereDate('requested_etd', '>=', $date);
        }

        if ($date = $request->query('requested_etd_to')) {
            $query->whereDate('requested_etd', '<=', $date);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'reference' => ['required', 'string', 'max:30', ...$this->uniqueRules('reference', $model)],
            'status' => ['sometimes', 'string', Rule::enum(BookingStatus::class)],

            'shipper_id' => ['sometimes', 'nullable', $this->partyOfType(PartyType::Shipper)],
            'consignee_id' => ['sometimes', 'nullable', $this->partyOfType(PartyType::Consignee)],
            'carrier_id' => ['sometimes', 'nullable', 'integer', Rule::exists('carriers', 'id')->whereNull('deleted_at')],
            'vessel_id' => ['sometimes', 'nullable', 'integer', Rule::exists('vessels', 'id')->whereNull('deleted_at')],
            'port_of_loading_id' => ['sometimes', 'nullable', 'integer', Rule::exists('ports', 'id')->whereNull('deleted_at')],
            'port_of_discharge_id' => ['sometimes', 'nullable', 'integer', Rule::exists('ports', 'id')->whereNull('deleted_at')],
            'origin_site_id' => ['sometimes', 'nullable', 'integer', Rule::exists('sites', 'id')->whereNull('deleted_at')],
            'destination_site_id' => ['sometimes', 'nullable', 'integer', Rule::exists('sites', 'id')->whereNull('deleted_at')],

            'incoterm' => ['sometimes', 'nullable', Rule::enum(Incoterm::class)],
            'incoterm_place' => ['sometimes', 'nullable', 'string', 'max:120'],

            'cargo_description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pieces' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'weight_kg' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'volume_cbm' => ['sometimes', 'nullable', 'numeric', 'min:0'],

            'requested_etd' => ['sometimes', 'nullable', 'date'],
            'requested_eta' => $this->onOrAfter($request, $model, 'requested_eta', 'requested_etd'),

            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
