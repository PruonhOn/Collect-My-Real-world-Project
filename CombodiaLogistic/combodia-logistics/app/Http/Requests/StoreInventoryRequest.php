<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-inventory');
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'warehouse_shelf_id' => 'nullable|exists:warehouse_shelves,id',
            'product_name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:inventories,sku',
            'barcode' => 'nullable|string|max:100|unique:inventories,barcode',
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
            'warehouse_id.exists' => 'ឃ្លាំងនេះមិនមានទេ',
            'product_name.required' => 'សូមបញ្ចូលឈ្មោះផលិតផល',
            'product_name.max' => 'ឈ្មោះផលិតផលមិនអាចលើសពី 255 អក្សរទេ',
            'sku.unique' => 'SKU នេះមានរួចហើយ',
            'barcode.unique' => 'Barcode នេះមានរួចហើយ',
            'unit.required' => 'សូមបញ្ចូលឯកតា',
            'quantity.required' => 'សូមបញ្ចូលចំនួន',
            'quantity.integer' => 'ចំនួនត្រូវតែជាលេខគត់',
            'quantity.min' => 'ចំនួនមិនអាចតូចជាង 0 បានទេ',
            'unit_cost.required' => 'សូមបញ្ចូលតម្លៃឯកតា',
            'unit_cost.numeric' => 'តម្លៃឯកតាត្រូវតែជាលេខ',
            'unit_cost.min' => 'តម្លៃឯកតាមិនអាចតូចជាង 0 បានទេ',
            'selling_price.required' => 'សូមបញ្ចូលតម្លៃលក់',
            'selling_price.numeric' => 'តម្លៃលក់ត្រូវតែជាលេខ',
            'selling_price.min' => 'តម្លៃលក់មិនអាចតូចជាង 0 បានទេ',
        ];
    }
}
