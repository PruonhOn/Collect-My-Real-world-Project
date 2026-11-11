<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRouteRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('route');

        return [
            'code' => 'required|string|max:50|unique:routes,code,' . $id,
            'name' => 'required|string|max:255',
            'origin_city' => 'required|string|max:100',
            'destination_city' => 'required|string|max:100',
            'distance_km' => 'nullable|numeric|min:0',
            'estimated_hours' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'waypoints' => 'nullable|json',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'The route code is required.',
            'code.unique' => 'This route code is already in use.',
            'name.required' => 'The route name is required.',
            'origin_city.required' => 'The origin city is required.',
            'destination_city.required' => 'The destination city is required.',
        ];
    }
}
