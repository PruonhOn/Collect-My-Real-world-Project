<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * A receipt: what came in, how, and when.
     *
     * `paid_at` is ISO-8601 and not `Y-m-d`, unlike `InvoiceResource`. The two are
     * different facts and the difference is load-bearing: an invoice is *dated* -
     * "issued on the 3rd" is what a customer cites and what a late test compares -
     * whereas a payment is an *event* that happened at a moment, and a day's
     * takings is cut by the clock rather than by the day. Truncating it to a date
     * would let a payment made at 23:50 be counted in a different reconciliation
     * from the one it was entered in.
     *
     * The amount is a decimal string for the reason every other money column in
     * this API is one, and `needs_reconciliation` is sent rather than derived in
     * the client: whether a receipt has to be matched to a bank statement before
     * it can be trusted is a property of the method, and a worklist that decided
     * it locally would be a second translation of a rule the server already owns.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,

            'invoice' => $this->whenLoaded('invoice', fn () => $this->invoice === null ? null : [
                'id' => $this->invoice->id,
                'reference' => $this->invoice->reference,
                'status' => $this->invoice->status?->value,
                'status_label' => $this->invoice->status?->label(),
                'total' => $this->invoice->total,
                'balance' => $this->invoice->balance,
                'due_date' => $this->invoice->due_date?->format('Y-m-d'),
            ]),

            'customer' => $this->whenLoaded('customer', fn () => $this->customer === null ? null : [
                'id' => $this->customer->id,
                'code' => $this->customer->code,
                'name' => $this->customer->name,
            ]),

            'amount' => $this->amount,
            'method' => $this->method?->value,
            'method_label' => $this->method?->label(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'bank_reference' => $this->bank_reference,

            'is_receipt' => $this->isReceipt(),
            'needs_reconciliation' => $this->needsReconciliation(),

            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
