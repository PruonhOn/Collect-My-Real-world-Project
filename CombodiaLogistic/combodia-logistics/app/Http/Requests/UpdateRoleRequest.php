<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-roles');
    }

    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name' => 'required|string|max:255|unique:roles,name,' . $roleId,
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'សូមបញ្ចូលឈ្មោះសិទ្ធិ',
            'name.unique' => 'ឈ្មោះសិទ្ធិនេះមានរួចហើយ',
            'permissions.array' => 'សូមជ្រើសរើសសិទ្ធិយ៉ាងហោចណាស់មួយ',
        ];
    }
}
