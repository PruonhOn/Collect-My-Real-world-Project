<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fee' => $this->whenLoaded('studentFee', fn () => [
                'id' => $this->studentFee->id,
                'invoice_no' => $this->studentFee->invoice_no,
                'title' => $this->studentFee->title,
            ]),
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->user?->name ?? null,
                'student_code' => $this->student->student_code,
            ]),
            'amount' => $this->amount,
            'method' => $this->method,
            'gateway' => $this->gateway,
            'transaction_id' => $this->transaction_id,
            'status' => $this->status?->value,
            'paid_on' => $this->paid_on?->toISOString(),
            'notes' => $this->notes,
            'receipt' => $this->whenLoaded('receipt', fn () => $this->receipt ? [
                'id' => $this->receipt->id,
                'receipt_no' => $this->receipt->receipt_no,
            ] : null),
        ];
    }
}
