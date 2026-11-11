<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name',
            'slug' => 'nullable|string|max:255|unique:roles,slug',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The role name is required.',
            'name.unique' => 'This role name is already in use.',
            'slug.unique' => 'This role slug is already in use.',
            'permissions.*.exists' => 'One or more selected permissions are invalid.',
        ];
    }
}
