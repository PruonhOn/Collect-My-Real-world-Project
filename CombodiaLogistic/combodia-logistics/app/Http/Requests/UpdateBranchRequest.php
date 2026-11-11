<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-branches');
    }

    public function rules(): array
    {
        $branchId = $this->route('branch')?->id;

        return [
            'code' => 'required|string|max:50|unique:branches,code,' . $branchId,
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'សូមបញ្ចូលកូដសាខា',
            'name.required' => 'សូមបញ្ចូលឈ្មោះសាខា',
            'address.required' => 'សូមបញ្ចូលអាសយដ្ឋាន',
            'city.required' => 'សូមបញ្ចូលទីក្រុង',
            'phone.required' => 'សូមបញ្ចូលលេខទូរស័ព្ទ',
        ];
    }
}
