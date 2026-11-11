<?php

namespace App\Http\Resources;

use App\Models\ShipmentContainer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShipmentContainer
 */
class ShipmentContainerResource extends JsonResource
{
    /**
     * A container is a few figures and a state, so there is very little to reduce.
     * The consignment is the exception, and it is reduced to the handful of fields
     * that put a row in context, for the same reason ShipmentTrackingResource
     * reduces it: a manifest of bare box numbers would force whoever reads the list
     * to open each consignment to find out whose cargo it is.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'container_number' => $this->container_number,
            'iso_code' => $this->iso_code,
            'type' => $this->type,

            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),

            // The consignment, reduced. Its reference and state are what a box is
            // read against, and the consignee and discharge port are what an operator
            // needs to decide whether it is worth chasing - all of which would be a
            // full shipment payload on every row of a paged manifest.
            'shipment' => $this->whenLoaded('shipment', fn () => $this->shipment === null ? null : [
                'id' => $this->shipment->id,
                'reference' => $this->shipment->reference,
                'status' => $this->shipment->status->value,
                'status_label' => $this->shipment->status->label(),
                'is_closed' => $this->shipment->status->isClosed(),
                // The port the box is bound for, so a held container can be read
                // without going back to the consignment to find out which border it
                // is stuck at.
                'port_of_discharge' => $this->shipment->portOfDischarge === null ? null : [
                    'id' => $this->shipment->portOfDischarge->id,
                    'un_locode' => $this->shipment->portOfDischarge->un_locode,
                    'name' => $this->shipment->portOfDischarge->name,
                ],
                'consignee' => $this->shipment->consignee === null ? null : [
                    'id' => $this->shipment->consignee->id,
                    'code' => $this->shipment->consignee->code,
                    'name' => $this->shipment->consignee->name,
                ],
            ]),

            'seal_number' => $this->seal_number,

            'tare_weight_kg' => $this->tare_weight_kg,
            'max_gross_weight_kg' => $this->max_gross_weight_kg,
            'gross_weight_kg' => $this->gross_weight_kg,

            /*
             * Derived, and sent for the reason ShipmentResource sends is_late: the
             * worklist filter and the badge on a row must not be able to disagree,
             * so the comparison is written once and both read the same expression.
             */
            'utilisation_percent' => $this->utilisationPercent(),
            'is_empty' => $this->isEmpty(),
            'needs_attention' => $this->needsAttention(),
            'is_closed' => $this->isClosed(),

            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
