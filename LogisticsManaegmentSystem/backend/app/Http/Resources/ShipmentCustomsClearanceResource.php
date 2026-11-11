<?php

namespace App\Http\Resources;

use App\Models\ShipmentCustomsClearance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShipmentCustomsClearance
 */
class ShipmentCustomsClearanceResource extends JsonResource
{
    /**
     * A clearance record is a state and three dates, so there is very little to
     * reduce. The consignment and the port are the exception, and both are
     * conditional rather than assumed: a port can be deleted from the lookup table
     * without this row losing its history, so `port` is allowed to be null here and
     * the client has to cope with that.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // The consignment, reduced. Its reference is what a clearance is read
            // against and an operator is quoting a consignment reference, not a
            // clearance id, when they ask the broker a question.
            'shipment' => $this->whenLoaded('shipment', fn () => $this->shipment === null ? null : [
                'id' => $this->shipment->id,
                'reference' => $this->shipment->reference,
                'status' => $this->shipment->status->value,
                'status_label' => $this->shipment->status->label(),
                'is_closed' => $this->shipment->status->isClosed(),
                'is_late' => $this->shipment->isLate(),
            ]),

            // Nullable by design - see the model. A removed port must not put a hole
            // in a cargo manifest, and the row still says what happened.
            'port' => $this->whenLoaded('port', fn () => $this->port === null ? null : [
                'id' => $this->port->id,
                'un_locode' => $this->port->un_locode,
                'name' => $this->port->name,
                'country_code' => $this->port->country_code,
            ]),

            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),

            'entry_number' => $this->entry_number,
            'broker' => $this->broker,

            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'cleared_at' => $this->cleared_at?->toIso8601String(),

            /*
             * How long the filing has been with the authority, derived and sent so
             * the worklist and the row cannot disagree about which of them is stuck.
             * Null until it has actually been submitted: a filing that has not gone
             * in has not been waiting for anything.
             */
            'is_held' => $this->isHeld(),
            'needs_attention' => $this->needsAttention(),
            'is_closed' => $this->isClosed(),

            'hold_reason' => $this->hold_reason,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
