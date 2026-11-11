<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('warehouse');

        return [
            'code' => 'required|string|max:50|unique:warehouses,code,' . $id,
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager_name' => 'nullable|string|max:255',
            'total_capacity' => 'nullable|numeric|min:0',
            'used_capacity' => 'nullable|numeric|min:0',
            'total_area' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'branch_id' => 'nullable|exists:branches,id',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'The warehouse code is required.',
            'code.unique' => 'This warehouse code is already in use.',
            'name.required' => 'The warehouse name is required.',
            'email.email' => 'Please provide a valid email address.',
            'branch_id.exists' => 'The selected branch is invalid.',
        ];
    }
}
