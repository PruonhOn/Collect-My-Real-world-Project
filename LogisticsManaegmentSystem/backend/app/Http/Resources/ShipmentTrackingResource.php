<?php

namespace App\Http\Resources;

use App\Models\ShipmentTracking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShipmentTracking
 */
class ShipmentTrackingResource extends JsonResource
{
    /**
     * A tracking row is small - seven fields and one reference - so there is
     * nothing to reduce. The consignment is the exception: it is reduced to the
     * handful of fields that put a row in context, because a worklist that only
     * said "transshipment" would make the operator go and open the consignment to
     * find out which one.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // The consignment, reduced. Its own reference, status and carrier are
            // what a row is read against, and its parties are what an operator
            // needs to decide whether it is worth chasing - all of which would be
            // a full shipment payload, plus a vessel and eleven other references,
            // on every row of a paged worklist.
            'shipment' => $this->whenLoaded('shipment', fn () => $this->shipment === null ? null : [
                'id' => $this->shipment->id,
                'reference' => $this->shipment->reference,
                'status' => $this->shipment->status->value,
                'status_label' => $this->shipment->status->label(),
                'is_closed' => $this->shipment->status->isClosed(),
                'is_late' => $this->shipment->isLate(),
                // The planned arrival, so a revised date has something to be read
                // against. Without it the client can only show the promise, and a
                // promise is only alarming next to the plan it replaced - which
                // would put the judgement back on whoever reads the row.
                'eta' => $this->shipment->eta?->format('Y-m-d'),
                'carrier' => $this->shipment->carrier === null ? null : [
                    'id' => $this->shipment->carrier->id,
                    'code' => $this->shipment->carrier->code,
                    'name' => $this->shipment->carrier->name,
                ],
                'shipper' => $this->shipment->shipper === null ? null : [
                    'id' => $this->shipment->shipper->id,
                    'code' => $this->shipment->shipper->code,
                    'name' => $this->shipment->shipper->name,
                ],
                'consignee' => $this->shipment->consignee === null ? null : [
                    'id' => $this->shipment->consignee->id,
                    'code' => $this->shipment->consignee->code,
                    'name' => $this->shipment->consignee->name,
                ],
                'port_of_discharge' => $this->shipment->portOfDischarge === null ? null : [
                    'id' => $this->shipment->portOfDischarge->id,
                    'un_locode' => $this->shipment->portOfDischarge->un_locode,
                    'name' => $this->shipment->portOfDischarge->name,
                ],
            ]),

            'last_milestone' => $this->last_milestone?->value,
            'last_milestone_label' => $this->last_milestone?->label(),
            'is_problem' => $this->last_milestone?->isProblem() ?? false,

            'current_location' => $this->current_location,
            'last_reported_at' => $this->last_reported_at?->toIso8601String(),

            // What the carrier has since promised, kept beside the shipment's own
            // ETA rather than over it. The client shows the two side by side,
            // which is the only way the difference is visible at all.
            'revised_eta' => $this->revised_eta?->format('Y-m-d'),

            'delay_reason' => $this->delay_reason,
            'source' => $this->source,

            // Derived, for the same reason ShipmentResource derives is_late: so the
            // worklist filter and the badge on a row cannot disagree, and so the
            // comparison is written once.
            'is_delayed' => $this->isDelayed(),
            'is_unreported' => $this->isUnreported(),

            // Sent because the alternative is the browser holding its own copy of
            // the three-day rule, and the copy that drifts is the one that badges
            // a row "current" while the worklist it came from said otherwise.
            'is_stale' => $this->isStale(),

            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
