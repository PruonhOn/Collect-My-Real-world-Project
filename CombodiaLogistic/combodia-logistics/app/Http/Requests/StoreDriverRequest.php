<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-drivers');
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'code' => 'required|string|max:50|unique:drivers,code',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'license_number' => 'required|string|max:50|unique:drivers,license_number',
            'license_expiry' => 'required|date|after:today',
            'date_of_birth' => 'nullable|date|before:today',
            'status' => 'required|string|in:active,on_leave,inactive',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'សូមជ្រើសរើសសាខា',
            'branch_id.exists' => 'សាខានេះមិនមានទេ',
            'code.required' => 'សូមបញ្ចូលកូដអ្នកបើកបរ',
            'code.unique' => 'កូដអ្នកបើកបរនេះមានរួចហើយ',
            'name.required' => 'សូមបញ្ចូលឈ្មោះអ្នកបើកបរ',
            'phone.required' => 'សូមបញ្ចូលលេខទូរស័ព្ទ',
            'license_number.required' => 'សូមបញ្ចូលលេខអនុញ្ញាតបើកបរ',
            'license_number.unique' => 'លេខអនុញ្ញាតបើកបរនេះមានរួចហើយ',
            'license_expiry.required' => 'សូមបញ្ចូលថ្ងៃផុតកំណត់អនុញ្ញាត',
            'license_expiry.after' => 'ថ្ងៃផុតកំណត់អនុញ្ញាតត្រូវតែនាពេលអនាគត',
            'email.email' => 'សូមបញ្ចូលអ៊ីមែលឱ្យបានត្រឹមត្រូវ',
        ];
    }
}
