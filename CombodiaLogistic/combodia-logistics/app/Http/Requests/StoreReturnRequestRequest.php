<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReturnRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-returns');
    }

    public function rules(): array
    {
        return [
            'shipment_id' => 'nullable|exists:shipments,id',
            'customer_id' => 'required|exists:customers,id',
            'branch_id' => 'nullable|exists:branches,id',
            'type' => 'required|string|max:255',
            'reason' => 'required|string|max:1000',
            'description' => 'nullable|string|max:1000',
            'total_items' => 'nullable|integer|min:0',
            'total_refund_amount' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'សូមជ្រើសរើសអតិថិជន',
            'customer_id.exists' => 'អតិថិជននេះមិនមានទេ',
            'type.required' => 'សូមបញ្ចូលប្រភេទការត្រឡប់',
            'reason.required' => 'សូមបញ្ចូលមូលហេតុការត្រឡប់',
            'reason.max' => 'មូលហេតុមិនអាចលើសពី 1000 អក្សរទេ',
            'total_items.integer' => 'ចំនួនទំនិញត្រូវតែជាលេខគត់',
            'total_items.min' => 'ចំនួនទំនិញមិនអាចតូចជាង 0 បានទេ',
            'total_refund_amount.numeric' => 'ចំនួនសងប្រាក់ត្រូវតែជាលេខ',
            'total_refund_amount.min' => 'ចំនួនសងប្រាក់មិនអាចតូចជាង 0 បានទេ',
        ];
    }
}
