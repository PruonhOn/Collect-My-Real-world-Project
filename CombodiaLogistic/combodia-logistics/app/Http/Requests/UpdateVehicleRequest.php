<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-vehicles');
    }

    public function rules(): array
    {
        $vehicleId = $this->route('vehicle')?->id;

        return [
            'branch_id' => 'required|exists:branches,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'code' => 'required|string|max:50|unique:vehicles,code,' . $vehicleId,
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number,' . $vehicleId,
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:50',
            'capacity_weight' => 'required|numeric|min:0',
            'capacity_volume' => 'required|numeric|min:0',
            'fuel_type' => 'nullable|string|max:50',
            'status' => 'required|string|in:active,maintenance,retired',
            'insurance_expiry' => 'nullable|date',
            'registration_expiry' => 'nullable|date',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'សូមជ្រើសរើសសាខា',
            'vehicle_type_id.required' => 'សូមជ្រើសរើសប្រភេទយានជំនិះ',
            'code.required' => 'សូមបញ្ចូលកូដយានជំនិះ',
            'plate_number.required' => 'សូមបញ្ចូលលេខសម្គាល់',
            'brand.required' => 'សូមបញ្ចូលម៉ាក',
            'model.required' => 'សូមបញ្ចូលម៉ូដែល',
            'year.required' => 'សូមបញ្ចូលឆ្នាំ',
            'capacity_weight.required' => 'សូមបញ្ចូលសមត្ថភាពទម្ងន់',
            'capacity_volume.required' => 'សូមបញ្ចូលសមត្ថភាពទំហំ',
        ];
    }
}
