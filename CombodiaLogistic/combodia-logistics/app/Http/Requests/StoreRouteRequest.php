<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-routes');
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:routes,code',
            'description' => 'nullable|string|max:1000',
            'start_location' => 'required|string|max:255',
            'end_location' => 'required|string|max:255',
            'distance' => 'required|numeric|min:0',
            'estimated_duration' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'សូមជ្រើសរើសសាខា',
            'branch_id.exists' => 'សាខានេះមិនមានទេ',
            'name.required' => 'សូមបញ្ចូលឈ្មោះផ្លូវ',
            'code.required' => 'សូមបញ្ចូលកូដផ្លូវ',
            'code.unique' => 'កូដផ្លូវនេះមានរួចហើយ',
            'start_location.required' => 'សូមបញ្ចូលទីតាំងចាប់ផ្ដើម',
            'end_location.required' => 'សូមបញ្ចូលទីតាំងបញ្ចប់',
            'distance.required' => 'សូមបញ្ចូលចម្ងាយ',
            'distance.numeric' => 'ចម្ងាយត្រូវតែជាលេខ',
            'distance.min' => 'ចម្ងាយមិនអាចតូចជាង 0 បានទេ',
        ];
    }
}
