<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('driver');

        return [
            'code' => 'required|string|max:50|unique:drivers,code,' . $id,
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255|unique:drivers,email,' . $id,
            'license_number' => 'required|string|max:100|unique:drivers,license_number,' . $id,
            'license_type' => 'nullable|string|max:50',
            'license_expiry' => 'nullable|date',
            'date_of_birth' => 'nullable|date',
            'national_id' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'status' => 'required|string|max:50',
            'hire_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'photo' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'branch_id' => 'nullable|exists:branches,id',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'The driver code is required.',
            'code.unique' => 'This driver code is already in use.',
            'name.required' => 'The driver name is required.',
            'phone.required' => 'The driver phone number is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already in use.',
            'license_number.required' => 'The license number is required.',
            'license_number.unique' => 'This license number is already in use.',
            'status.required' => 'The driver status is required.',
            'photo.image' => 'The photo must be an image file.',
            'photo.max' => 'The photo must not exceed 2MB.',
            'branch_id.exists' => 'The selected branch is invalid.',
        ];
    }
}
