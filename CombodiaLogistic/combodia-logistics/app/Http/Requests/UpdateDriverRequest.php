<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-drivers');
    }

    public function rules(): array
    {
        $driverId = $this->route('driver')?->id;

        return [
            'user_id' => 'nullable|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'code' => 'required|string|max:50|unique:drivers,code,' . $driverId,
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'license_number' => 'required|string|max:50|unique:drivers,license_number,' . $driverId,
            'license_expiry' => 'required|date',
            'date_of_birth' => 'nullable|date|before:today',
            'status' => 'required|string|in:active,on_leave,inactive',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'សូមជ្រើសរើសសាខា',
            'code.required' => 'សូមបញ្ចូលកូដអ្នកបើកបរ',
            'name.required' => 'សូមបញ្ចូលឈ្មោះអ្នកបើកបរ',
            'phone.required' => 'សូមបញ្ចូលលេខទូរស័ព្ទ',
            'license_number.required' => 'សូមបញ្ចូលលេខអនុញ្ញាតបើកបរ',
            'license_expiry.required' => 'សូមបញ្ចូលថ្ងៃផុតកំណត់អនុញ្ញាត',
        ];
    }
}
