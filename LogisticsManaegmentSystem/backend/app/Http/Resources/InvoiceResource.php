<?php

namespace App\Http\Resources;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Invoice
 */
class InvoiceResource extends JsonResource
{
    /**
     * A demand for money, with everything a receivables screen needs and nothing
     * it has to compute.
     *
     * The five amounts are decimal strings for the reason `ChargeResource` gives:
     * a `decimal(15,4)` read into a JS number loses the trailing zeros the column
     * means, and a total re-added in the browser would then differ from the one
     * the server computed from the stored lines. A finance officer has to be able
     * to add the invoice up by hand and get the number this says.
     *
     * The dates are `Y-m-d` and not ISO-8601, unlike every other resource in this
     * API. An invoice is dated, not stamped: "issued on the 3rd" is the fact a
     * customer cites in a dispute, and a time on it would only ever be the moment
     * somebody clicked save. The late test is a comparison of days, so a
     * timestamp here would let a timezone decide whether an invoice was overdue.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,

            'customer' => $this->whenLoaded('customer', fn () => $this->customer === null ? null : [
                'id' => $this->customer->id,
                'code' => $this->customer->code,
                'name' => $this->customer->name,
            ]),

            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),

            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),

            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'paid_amount' => $this->paid_amount,
            'balance' => $this->balance,
            'currency' => $this->currency,

            /*
             * Derived, and sent rather than left to the client. `is_overdue` and
             * `days_overdue` are the same rule the worklist filter uses, which is
             * what stops a row on a receivables list and a tile on the dashboard
             * disagreeing about what is late. `is_outstanding` is deliberately a
             * strict positive: an overpayment is a negative balance, and that is
             * money this business owes the customer, not money it is owed.
             */
            'is_outstanding' => $this->isOutstanding(),
            'is_settled' => $this->isSettled(),
            'is_overdue' => $this->isOverdue(),
            'days_overdue' => $this->daysOverdue(),
            'is_editable' => $this->isEditable(),

            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
