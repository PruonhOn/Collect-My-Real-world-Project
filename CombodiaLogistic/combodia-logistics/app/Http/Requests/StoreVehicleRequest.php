<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-vehicles');
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'code' => 'required|string|max:50|unique:vehicles,code',
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:50',
            'capacity_weight' => 'required|numeric|min:0',
            'capacity_volume' => 'required|numeric|min:0',
            'fuel_type' => 'nullable|string|max:50',
            'status' => 'required|string|in:active,maintenance,retired',
            'insurance_expiry' => 'nullable|date|after:today',
            'registration_expiry' => 'nullable|date|after:today',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'សូមជ្រើសរើសសាខា',
            'branch_id.exists' => 'សាខានេះមិនមានទេ',
            'vehicle_type_id.required' => 'សូមជ្រើសរើសប្រភេទយានជំនិះ',
            'code.required' => 'សូមបញ្ចូលកូដយានជំនិះ',
            'code.unique' => 'កូដយានជំនិះនេះមានរួចហើយ',
            'plate_number.required' => 'សូមបញ្ចូលលេខសម្គាល់',
            'plate_number.unique' => 'លេខសម្គាល់នេះមានរួចហើយ',
            'brand.required' => 'សូមបញ្ចូលម៉ាក',
            'model.required' => 'សូមបញ្ចូលម៉ូដែល',
            'year.required' => 'សូមបញ្ចូលឆ្នាំ',
            'year.integer' => 'ឆ្នាំត្រូវតែជាលេខគត់',
            'capacity_weight.required' => 'សូមបញ្ចូលសមត្ថភាពទម្ងន់',
            'capacity_volume.required' => 'សូមបញ្ចូលសមត្ថភាពទំហំ',
            'insurance_expiry.after' => 'ថ្ងៃផុតកំណត់ធានារ៉ាប់រងត្រូវតែនាពេលអនាគត',
            'registration_expiry.after' => 'ថ្ងៃផុតកំណត់សម្គាល់ត្រូវតែនាពេលអនាគត',
        ];
    }
}
