<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => 'required|string|max:100|unique:inventory_items,sku',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|max:100',
            'quantity' => 'required|numeric|min:0',
            'min_quantity' => 'nullable|numeric|min:0',
            'max_quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'weight_per_unit' => 'nullable|numeric|min:0',
            'volume_per_unit' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'barcode' => 'nullable|string|max:255',
            'qr_code' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'warehouse_id' => 'required|exists:warehouses,id',
            'location_id' => 'nullable|exists:warehouse_locations,id',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'The SKU is required.',
            'sku.unique' => 'This SKU is already in use.',
            'name.required' => 'The item name is required.',
            'category.required' => 'The item category is required.',
            'quantity.required' => 'The quantity is required.',
            'quantity.min' => 'The quantity must be a positive value.',
            'warehouse_id.required' => 'The warehouse is required.',
            'warehouse_id.exists' => 'The selected warehouse is invalid.',
            'location_id.exists' => 'The selected location is invalid.',
        ];
    }
}
