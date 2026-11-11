<?php

namespace App\Http\Resources;

use App\Models\Charge;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Charge
 */
class ChargeResource extends JsonResource
{
    /**
     * A line on a bill, plus the consignment it was raised for and the invoice it
     * ended up on.
     *
     * The three totals are sent as the decimal strings the columns hold, not as
     * numbers. A `decimal(15,4)` read into a JS number is a float, and the
     * trailing zeros the column means are gone - so a form round-tripping
     * `8.5000` would send `8.5`, and a total re-added in the browser would
     * differ from the one the server computed. The string is the value.
     *
     * The invoice and the consignment are both conditional, as everywhere in this
     * API: `preventLazyLoading` is on outside production, so a relation not in
     * the controller's eager loads would raise here rather than quietly issuing a
     * query per row. The client can rely on these keys being present and only
     * sometimes being null - and `invoice` is genuinely null for a line that is
     * still being quoted, which is the normal state of a charge on a live
     * consignment.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,

            'shipment' => $this->whenLoaded('shipment', fn () => $this->shipment === null ? null : [
                'id' => $this->shipment->id,
                'reference' => $this->shipment->reference,
                'status' => $this->shipment->status->value,
                'status_label' => $this->shipment->status->label(),
            ]),

            /*
             * The invoice, reduced. It carries the status rather than the charge
             * carrying a copy of it, which is the whole point: the question "has
             * this been paid" belongs to the invoice, and a line that answered it
             * independently could contradict the bill it is printed on.
             */
            'invoice' => $this->whenLoaded('invoice', fn () => $this->invoice === null ? null : [
                'id' => $this->invoice->id,
                'reference' => $this->invoice->reference,
                'status' => $this->invoice->status?->value,
                'status_label' => $this->invoice->status?->label(),
                'balance' => $this->invoice->balance,
                'due_date' => $this->invoice->due_date?->format('Y-m-d'),
            ]),

            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),

            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),

            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'tax_rate' => $this->tax_rate,

            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'currency' => $this->currency,

            /*
             * Derived, and sent for the reason every other resource does: the list
             * filter and the badge on a row must not be able to disagree, so both
             * read this one expression rather than each reaching its own
             * conclusion about what "invoiced" means.
             */
            'is_billable' => $this->isBillable(),
            'is_invoiced' => $this->isInvoiced(),
            'is_settled' => $this->isSettled(),

            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
