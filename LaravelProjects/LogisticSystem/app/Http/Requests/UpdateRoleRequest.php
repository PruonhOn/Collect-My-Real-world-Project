<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('role');

        return [
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'slug' => 'nullable|string|max:255|unique:roles,slug,' . $id,
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
