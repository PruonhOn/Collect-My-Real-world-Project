<?php

namespace App\Http\Resources;

use App\Models\ShipmentDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShipmentDelivery
 */
class ShipmentDeliveryResource extends JsonResource
{
    /**
     * A delivery is a status, three timestamps and a vehicle, so there is little to
     * reduce. The consignment, the vehicle and the driver are all conditional:
     * `preventLazyLoading` is on outside production, so a relation not listed in
     * the controller's eager loads would raise here rather than quietly querying -
     * which means the client can rely on these three always being present as keys,
     * and only sometimes being non-null.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,

            // The consignment, reduced. Its reference is what a delivery is read
            // against, and an operator is standing at a door with a consignment
            // reference on the paperwork rather than a delivery id.
            'shipment' => $this->whenLoaded('shipment', fn () => $this->shipment === null ? null : [
                'id' => $this->shipment->id,
                'reference' => $this->shipment->reference,
                'status' => $this->shipment->status->value,
                'status_label' => $this->shipment->status->label(),
                'is_closed' => $this->shipment->status->isClosed(),
                'is_late' => $this->shipment->isLate(),
                'consignee' => $this->shipment->consignee === null ? null : [
                    'id' => $this->shipment->consignee->id,
                    'code' => $this->shipment->consignee->code,
                    'name' => $this->shipment->consignee->name,
                ],
            ]),

            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),

            'truck' => $this->whenLoaded('truck', fn () => $this->truck === null ? null : [
                'id' => $this->truck->id,
                'code' => $this->truck->code,
                'plate_number' => $this->truck->plate_number,
            ]),

            'driver' => $this->whenLoaded('driver', fn () => $this->driver === null ? null : [
                'id' => $this->driver->id,
                'code' => $this->driver->code,
                'name' => $this->driver->name,
            ]),

            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'picked_up_at' => $this->picked_up_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),

            /*
             * Derived, and sent for the reason every other resource does: the
             * worklist filter and the badge on a row must not be able to disagree, so
             * the overdue rule is written once and both read the same expression.
             */
            'is_overdue' => $this->isOverdue(),
            'is_outstanding' => $this->isOutstanding(),
            'is_moving' => $this->isMoving(),
            'has_failed' => $this->hasFailed(),
            'is_closed' => $this->isClosed(),

            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'delivery_address' => $this->delivery_address,
            'pod_reference' => $this->pod_reference,
            'failure_reason' => $this->failure_reason,

            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
