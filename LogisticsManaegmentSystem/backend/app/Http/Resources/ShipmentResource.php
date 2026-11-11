<?php

namespace App\Http\Resources;

use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Shipment
 */
class ShipmentResource extends JsonResource
{
    /**
     * A shipment carries twelve references, and nesting full resources for them
     * would drag a carrier's trucks, a truck's driver and a port's history into
     * every row of a paged list. Each is reduced to the fields a list can
     * actually display, which is also exactly what a picker needs to show the
     * option that was chosen.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_closed' => $this->status->isClosed(),

            // Reference and status, not the whole booking: the link is there to
            // say which request this came from, and the booking's own screen is
            // where its routing and cargo are read. Absent rather than null when
            // there is no booking, so the key is simply missing on a consignment
            // raised without one.
            'booking' => $this->whenLoaded('booking', fn () => $this->ref($this->booking, ['reference', 'status'])),

            'shipper' => $this->whenLoaded('shipper', fn () => $this->ref($this->shipper, ['code', 'name'])),
            'consignee' => $this->whenLoaded('consignee', fn () => $this->ref($this->consignee, ['code', 'name'])),
            'carrier' => $this->whenLoaded('carrier', fn () => $this->ref($this->carrier, ['code', 'name'])),
            'vessel' => $this->whenLoaded('vessel', fn () => $this->ref($this->vessel, ['name', 'imo_number'])),
            'port_of_loading' => $this->whenLoaded('portOfLoading', fn () => $this->ref($this->portOfLoading, ['un_locode', 'name'])),
            'port_of_discharge' => $this->whenLoaded('portOfDischarge', fn () => $this->ref($this->portOfDischarge, ['un_locode', 'name'])),
            'origin_site' => $this->whenLoaded('originSite', fn () => $this->ref($this->originSite, ['code', 'name'])),
            'destination_site' => $this->whenLoaded('destinationSite', fn () => $this->ref($this->destinationSite, ['code', 'name'])),
            'truck' => $this->whenLoaded('truck', fn () => $this->ref($this->truck, ['code', 'plate_number'])),
            'driver' => $this->whenLoaded('driver', fn () => $this->ref($this->driver, ['code', 'name'])),

            'incoterm' => $this->incoterm?->value,
            'incoterm_place' => $this->incoterm_place,

            'cargo_description' => $this->cargo_description,
            'pieces' => $this->pieces,
            'weight_kg' => $this->weight_kg,
            'volume_cbm' => $this->volume_cbm,

            // `Y-m-d` rather than a full timestamp: an ETD is a day on a booking
            // confirmation, and a time component invites a timezone bug for a
            // field that has none. The actual_* columns keep their time, because
            // "arrived at 06:40" is the fact being recorded.
            'etd' => $this->etd?->format('Y-m-d'),
            'eta' => $this->eta?->format('Y-m-d'),
            'actual_departure' => $this->actual_departure?->toIso8601String(),
            'actual_arrival' => $this->actual_arrival?->toIso8601String(),

            // Derived, so the UI can flag a late consignment without
            // recomputing the comparison in every component that renders a row.
            // Computed on the model, because the tracking worklist nests a reduced
            // copy of this same consignment and has to answer it identically.
            'is_late' => $this->isLate(),

            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Reduce a related record to `id` plus the given attributes.
     *
     * The attribute is dropped when null so a reference object has the same
     * shape whether or not the optional part was filled in - a truck always has
     * an id and a plate, a party has a code and a name, and neither ever carries
     * a key the other does not.
     *
     * @param  array<int, string>  $attributes
     * @return array<string, mixed>|null
     */
    private function ref(?object $related, array $attributes): ?array
    {
        if ($related === null) {
            return null;
        }

        $ref = ['id' => $related->id];

        foreach ($attributes as $attribute) {
            $ref[$attribute] = $related->{$attribute};
        }

        return $ref;
    }
}
