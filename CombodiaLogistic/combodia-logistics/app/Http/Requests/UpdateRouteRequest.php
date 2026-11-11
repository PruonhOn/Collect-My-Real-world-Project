<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-routes');
    }

    public function rules(): array
    {
        $routeId = $this->route('deliveryRoute')?->id;

        return [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:routes,code,' . $routeId,
            'description' => 'nullable|string|max:1000',
            'start_location' => 'required|string|max:255',
            'end_location' => 'required|string|max:255',
            'distance' => 'required|numeric|min:0',
            'estimated_duration' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'សូមជ្រើសរើសសាខា',
            'name.required' => 'សូមបញ្ចូលឈ្មោះផ្លូវ',
            'code.required' => 'សូមបញ្ចូលកូដផ្លូវ',
            'start_location.required' => 'សូមបញ្ចូលទីតាំងចាប់ផ្ដើម',
            'end_location.required' => 'សូមបញ្ចូលទីតាំងបញ្ចប់',
            'distance.required' => 'សូមបញ្ចូលចម្ងាយ',
        ];
    }
}
