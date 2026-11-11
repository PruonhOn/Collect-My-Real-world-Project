<?php

namespace App\Http\Requests\V1\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['payments.process', 'payments.make']) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['nullable', 'string', 'max:50'],
            'gateway' => ['nullable', 'string', 'max:50'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:191'],
        ];
    }
}
