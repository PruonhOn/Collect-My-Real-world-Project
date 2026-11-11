<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|max:50',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'customer_id' => 'required|exists:customers,id',
            'shipment_id' => 'nullable|exists:shipments,id',
            'branch_id' => 'nullable|exists:branches,id',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string|max:255',
            'items.*.type' => 'nullable|string|max:50',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.total' => 'required_with:items|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'The invoice type is required.',
            'invoice_date.required' => 'The invoice date is required.',
            'due_date.required' => 'The due date is required.',
            'due_date.after_or_equal' => 'The due date must be on or after the invoice date.',
            'subtotal.required' => 'The subtotal is required.',
            'total.required' => 'The total amount is required.',
            'status.required' => 'The invoice status is required.',
            'customer_id.required' => 'The customer is required.',
            'customer_id.exists' => 'The selected customer is invalid.',
            'shipment_id.exists' => 'The selected shipment is invalid.',
            'branch_id.exists' => 'The selected branch is invalid.',
            'items.*.description.required_with' => 'Each invoice item must have a description.',
            'items.*.quantity.required_with' => 'Each invoice item must have a quantity.',
            'items.*.unit_price.required_with' => 'Each invoice item must have a unit price.',
            'items.*.total.required_with' => 'Each invoice item must have a total.',
        ];
    }
}
