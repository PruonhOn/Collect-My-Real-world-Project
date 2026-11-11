<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-warehouses');
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'code' => 'required|string|max:50|unique:warehouses,code',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'capacity' => 'required|numeric|min:0',
            'manager_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'សូមជ្រើសរើសសាខា',
            'branch_id.exists' => 'សាខានេះមិនមានទេ',
            'code.required' => 'សូមបញ្ចូលកូដឃ្លាំង',
            'code.unique' => 'កូដឃ្លាំងនេះមានរួចហើយ',
            'name.required' => 'សូមបញ្ចូលឈ្មោះឃ្លាំង',
            'capacity.required' => 'សូមបញ្ចូលសមត្ថភាព',
            'capacity.numeric' => 'សមត្ថភាពត្រូវតែជាលេខ',
            'capacity.min' => 'សមត្ថភាពមិនអាចតូចជាង 0 បានទេ',
            'email.email' => 'សូមបញ្ចូលអ៊ីមែលឱ្យបានត្រឹមត្រូវ',
        ];
    }
}
