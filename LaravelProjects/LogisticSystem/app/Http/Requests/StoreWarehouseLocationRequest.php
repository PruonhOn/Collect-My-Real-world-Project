<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseLocationRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:warehouse_locations,code',
            'name' => 'required|string|max:255',
            'zone' => 'nullable|string|max:100',
            'aisle' => 'nullable|string|max:50',
            'rack' => 'nullable|string|max:50',
            'shelf' => 'nullable|string|max:50',
            'bin' => 'nullable|string|max:50',
            'max_weight' => 'nullable|numeric|min:0',
            'max_volume' => 'nullable|numeric|min:0',
            'is_occupied' => 'boolean',
            'is_active' => 'boolean',
            'warehouse_id' => 'required|exists:warehouses,id',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'The location code is required.',
            'code.unique' => 'This location code is already in use.',
            'name.required' => 'The location name is required.',
            'warehouse_id.required' => 'The warehouse is required.',
            'warehouse_id.exists' => 'The selected warehouse is invalid.',
        ];
    }
}
