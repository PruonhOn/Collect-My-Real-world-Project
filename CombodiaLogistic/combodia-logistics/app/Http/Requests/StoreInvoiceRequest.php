<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-invoices');
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'shipment_id' => 'nullable|exists:shipments,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'terms_conditions' => 'nullable|string|max:2000',
            'items' => 'nullable|array',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'សូមជ្រើសរើសអតិថិជន',
            'customer_id.exists' => 'អតិថិជននេះមិនមានទេ',
            'issue_date.required' => 'សូមបញ្ចូលថ្ងៃចេញវិក្កយបត្រ',
            'due_date.required' => 'សូមបញ្ចូលថ្ងៃផុតកំណត់បង់ប្រាក់',
            'due_date.after_or_equal' => 'ថ្ងៃផុតកំណត់បង់ប្រាក់ត្រូវតែនាពេលអនាគត',
            'subtotal.required' => 'សូមបញ្ចូលសរុបរង',
            'subtotal.numeric' => 'សរុបរងត្រូវតែជាលេខ',
            'subtotal.min' => 'សរុបរងមិនអាចតូចជាង 0 បានទេ',
            'tax_rate.required' => 'សូមបញ្ចូលអត្រាពន្ធ',
            'tax_rate.numeric' => 'អត្រាពន្ធត្រូវតែជាលេខ',
            'tax_rate.max' => 'អត្រាពន្ធមិនអាចលើសពី 100% បានទេ',
            'total_amount.required' => 'សូមបញ្ចូលចំនួនសរុប',
            'total_amount.numeric' => 'ចំនួនសរុបត្រូវតែជាលេខ',
            'total_amount.min' => 'ចំនួនសរុបមិនអាចតូចជាង 0 បានទេ',
        ];
    }
}
