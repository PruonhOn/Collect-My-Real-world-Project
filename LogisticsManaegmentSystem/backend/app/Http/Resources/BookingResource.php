<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    /**
     * A booking carries eight references, and nesting full resources for them
     * would drag a carrier's trucks and a port's history into every row of a paged
     * list. Each is reduced to the fields a list can actually display, which is
     * also exactly what a picker needs to show the option that was chosen.
     *
     * The shape deliberately mirrors ShipmentResource. The two records hold the
     * same routing and the same cargo, so a component that renders a booking can
     * be handed a shipment's row and vice versa - which matters because a
     * shipment raised from a booking is shown next to it.
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

            'shipper' => $this->whenLoaded('shipper', fn () => $this->ref($this->shipper, ['code', 'name'])),
            'consignee' => $this->whenLoaded('consignee', fn () => $this->ref($this->consignee, ['code', 'name'])),
            'carrier' => $this->whenLoaded('carrier', fn () => $this->ref($this->carrier, ['code', 'name'])),
            'vessel' => $this->whenLoaded('vessel', fn () => $this->ref($this->vessel, ['name', 'imo_number'])),
            'port_of_loading' => $this->whenLoaded('portOfLoading', fn () => $this->ref($this->portOfLoading, ['un_locode', 'name'])),
            'port_of_discharge' => $this->whenLoaded('portOfDischarge', fn () => $this->ref($this->portOfDischarge, ['un_locode', 'name'])),
            'origin_site' => $this->whenLoaded('originSite', fn () => $this->ref($this->originSite, ['code', 'name'])),
            'destination_site' => $this->whenLoaded('destinationSite', fn () => $this->ref($this->destinationSite, ['code', 'name'])),

            'incoterm' => $this->incoterm?->value,
            'incoterm_place' => $this->incoterm_place,

            'cargo_description' => $this->cargo_description,
            'pieces' => $this->pieces,
            'weight_kg' => $this->weight_kg,
            'volume_cbm' => $this->volume_cbm,

            // `Y-m-d` for the same reason as a shipment's etd: a requested sailing
            // is a day, and a time component invites a timezone bug for a field
            // that has none.
            'requested_etd' => $this->requested_etd?->format('Y-m-d'),
            'requested_eta' => $this->requested_eta?->format('Y-m-d'),

            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Reduce a related record to `id` plus the given attributes.
     *
     * The attribute is dropped when null so a reference object has the same shape
     * whether or not the optional part was filled in - a vessel always has a name,
     * a party always has a code - and neither ever carries a key the other does
     * not.
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
