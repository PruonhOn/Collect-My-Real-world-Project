<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'currency' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'invoice_id' => 'required|exists:invoices,id',
            'customer_id' => 'nullable|exists:customers,id',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_date.required' => 'The payment date is required.',
            'amount.required' => 'The payment amount is required.',
            'amount.min' => 'The payment amount must be a positive value.',
            'payment_method.required' => 'The payment method is required.',
            'invoice_id.required' => 'The invoice is required.',
            'invoice_id.exists' => 'The selected invoice is invalid.',
            'customer_id.exists' => 'The selected customer is invalid.',
        ];
    }
}
