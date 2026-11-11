<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($productId)],
            'description' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'brand' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp,gif', 'max:2048'],
            'existing_images' => ['nullable', 'array'],
            'existing_images.*' => ['string', 'max:255'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.size' => ['nullable', 'string', 'max:20'],
            'variants.*.color' => ['nullable', 'string', 'max:50'],
            'variants.*.sku_variant' => ['required', 'string', 'max:60', Rule::unique('product_variants', 'sku_variant')],
            'variants.*.barcode' => ['nullable', 'string', 'max:64', Rule::unique('product_variants', 'barcode')],
            'variants.*.stock_quantity' => ['required', 'integer', 'min:0'],
            'variants.*.price_override' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'variants.required' => 'At least one variant (size/color combination) is required.',
            'variants.min' => 'At least one variant (size/color combination) is required.',
            'variants.*.sku_variant.unique' => 'The variant SKU must be unique across the store.',
        ];
    }
}
