<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-payments');
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:3',
            'reference_number' => 'nullable|string|max:255',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'សូមជ្រើសរើសអតិថិជន',
            'customer_id.exists' => 'អតិថិជននេះមិនមានទេ',
            'payment_method_id.required' => 'សូមជ្រើសរើសវិធីបង់ប្រាក់',
            'payment_method_id.exists' => 'វិធីបង់ប្រាក់នេះមិនមានទេ',
            'amount.required' => 'សូមបញ្ចូលចំនួនទឹកប្រាក់',
            'amount.numeric' => 'ចំនួនទឹកប្រាក់ត្រូវតែជាលេខ',
            'amount.min' => 'ចំនួនទឹកប្រាក់មិនអាចតូចជាង 0.01 បានទេ',
            'payment_date.required' => 'សូមបញ្ចូលថ្ងៃបង់ប្រាក់',
        ];
    }
}
