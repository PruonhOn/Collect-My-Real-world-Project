<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-roles');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'សូមបញ្ចូលឈ្មោះសិទ្ធិ',
            'name.string' => 'ឈ្មោះសិទ្ធិត្រូវតែជាអត្ថបទ',
            'name.unique' => 'ឈ្មោះសិទ្ធិនេះមានរួចហើយ',
            'description.max' => 'ការពិពណ៌នាមិនអាចលើសពី 500 អក្សរទេ',
            'permissions.array' => 'សូមជ្រើសរើសសិទ្ធិយ៉ាងហោចណាស់មួយ',
        ];
    }
}
