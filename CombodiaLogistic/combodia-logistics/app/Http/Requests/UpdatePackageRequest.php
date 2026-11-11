<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit-shipments');
    }

    public function rules(): array
    {
        return [
            'shipment_id' => 'required|exists:shipments,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'warehouse_shelf_id' => 'nullable|exists:warehouse_shelves,id',
            'type' => 'nullable|string|max:255',
            'weight' => 'required|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:pending,stored,in_transit,delivered',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'shipment_id.required' => 'សូមជ្រើសរើសការដឹកជញ្ជូន',
            'weight.required' => 'សូមបញ្ចូលទម្ងន់',
        ];
    }
}
