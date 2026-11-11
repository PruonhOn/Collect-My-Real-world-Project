<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('vehicle');

        return [
            'code' => 'required|string|max:50|unique:vehicles,code,' . $id,
            'plate_number' => 'required|string|max:50|unique:vehicles,plate_number,' . $id,
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:50',
            'capacity_weight' => 'nullable|numeric|min:0',
            'capacity_volume' => 'nullable|numeric|min:0',
            'fuel_type' => 'nullable|string|max:50',
            'fuel_consumption' => 'nullable|numeric|min:0',
            'insurance_number' => 'nullable|string|max:255',
            'insurance_expiry' => 'nullable|date',
            'registration_number' => 'nullable|string|max:255',
            'registration_expiry' => 'nullable|date',
            'status' => 'required|string|max:50',
            'ownership' => 'nullable|string|max:50',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'is_active' => 'boolean',
            'vehicle_type_id' => 'nullable|exists:vehicle_types,id',
            'branch_id' => 'nullable|exists:branches,id',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'The vehicle code is required.',
            'code.unique' => 'This vehicle code is already in use.',
            'plate_number.required' => 'The plate number is required.',
            'plate_number.unique' => 'This plate number is already in use.',
            'make.required' => 'The vehicle make is required.',
            'model.required' => 'The vehicle model is required.',
            'status.required' => 'The vehicle status is required.',
            'year.min' => 'The year must be at least 1900.',
            'vehicle_type_id.exists' => 'The selected vehicle type is invalid.',
            'branch_id.exists' => 'The selected branch is invalid.',
        ];
    }
}
