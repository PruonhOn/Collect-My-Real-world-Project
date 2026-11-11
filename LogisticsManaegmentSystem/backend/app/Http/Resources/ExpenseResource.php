<?php

namespace App\Http\Resources;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Expense
 */
class ExpenseResource extends JsonResource
{
    /**
     * An outgoing cost, and the two different totals it can be counted in.
     *
     * `margin_amount` and `spend_amount` are both sent, and they are not the same
     * number. The difference is duty and tax: money collected from a customer and
     * remitted to the authority is something that passed through the business
     * rather than something it spent, so it belongs in a total-spend figure and
     * not in a margin one. Computing either in the browser would mean each chart
     * re-deciding that rule, and a margin report that is wrong by exactly the
     * amount of the tax is the kind of wrong a manager notices once and then
     * stops trusting the whole thing over.
     *
     * `incurred_on` is `Y-m-d` for the same reason it is a `date` column: a
     * carrier's demurrage invoice is dated the day the terminal charged it, which
     * is not the day it was keyed in, and a month-end figure has to land in the
     * month the cost belongs to.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,

            'category' => $this->category?->value,
            'category_label' => $this->category?->label(),

            'shipment' => $this->whenLoaded('shipment', fn () => $this->shipment === null ? null : [
                'id' => $this->shipment->id,
                'reference' => $this->shipment->reference,
                'status' => $this->shipment->status->value,
                'status_label' => $this->shipment->status->label(),
            ]),

            'booking' => $this->whenLoaded('booking', fn () => $this->booking === null ? null : [
                'id' => $this->booking->id,
                'reference' => $this->booking->reference,
            ]),

            'carrier' => $this->whenLoaded('carrier', fn () => $this->carrier === null ? null : [
                'id' => $this->carrier->id,
                'code' => $this->carrier->code,
                'name' => $this->carrier->name,
            ]),

            'vendor_name' => $this->vendor_name,
            'vendor_reference' => $this->vendor_reference,

            'amount' => $this->amount,
            'currency' => $this->currency,
            'incurred_on' => $this->incurred_on?->format('Y-m-d'),

            /*
             * The two questions an expense is asked, answered by the server.
             *
             * `is_billable` is intent - do we intend to recharge this - and it is
             * forced false for held money, because duty and tax is never a
             * recharged cost; it is owed to an authority. `counts_against_margin`
             * is the accounting rule, and is false for exactly the same case.
             * Sending both keeps "should we bill this on" (a decision) apart from
             * "does this eat our margin" (a rule), which are questions a single
             * boolean would answer identically and wrongly.
             */
            'is_billable' => $this->isBillable(),
            'counts_against_margin' => $this->countsAgainstMargin(),
            'margin_amount' => $this->marginAmount(),
            'spend_amount' => $this->spendAmount(),

            'description' => $this->description,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
