<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleMaintenanceRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference_no' => 'nullable|string|max:255',
            'maintenance_type' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'maintenance_date' => 'required|date',
            'next_maintenance_date' => 'nullable|date|after:maintenance_date',
            'cost' => 'required|numeric|min:0',
            'vendor' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'vehicle_id' => 'required|exists:vehicles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'maintenance_type.required' => 'The maintenance type is required.',
            'maintenance_date.required' => 'The maintenance date is required.',
            'cost.required' => 'The maintenance cost is required.',
            'cost.min' => 'The cost must be a positive value.',
            'status.required' => 'The maintenance status is required.',
            'vehicle_id.required' => 'The vehicle is required.',
            'vehicle_id.exists' => 'The selected vehicle is invalid.',
            'next_maintenance_date.after' => 'The next maintenance date must be after the maintenance date.',
        ];
    }
}
