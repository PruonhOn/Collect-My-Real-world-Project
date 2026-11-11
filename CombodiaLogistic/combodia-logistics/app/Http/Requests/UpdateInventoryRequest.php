<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-inventory');
    }

    public function rules(): array
    {
        $inventoryId = $this->route('inventory')?->id;

        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'warehouse_shelf_id' => 'nullable|exists:warehouse_shelves,id',
            'product_name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:inventories,sku,' . $inventoryId,
            'barcode' => 'nullable|string|max:100|unique:inventories,barcode,' . $inventoryId,
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'quantity' => 'required|integer|min:0',
            'min_quantity' => 'nullable|integer|min:0',
            'max_quantity' => 'nullable|integer|min:0',
            'reorder_point' => 'nullable|integer|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'សូមជ្រើសរើសឃ្លាំង',
            'product_name.required' => 'សូមបញ្ចូលឈ្មោះផលិតផល',
            'unit.required' => 'សូមបញ្ចូលឯកតា',
            'quantity.required' => 'សូមបញ្ចូលចំនួន',
            'unit_cost.required' => 'សូមបញ្ចូលតម្លៃឯកតា',
            'selling_price.required' => 'សូមបញ្ចូលតម្លៃលក់',
        ];
    }
}
