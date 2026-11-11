<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFacultyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Faculty name is required.',
            'name.string' => 'Faculty name must be a string.',
            'name.max' => 'Faculty name must not exceed 255 characters.',
            'description.string' => 'Description must be a string.',
            'description.max' => 'Description must not exceed 1000 characters.',
        ];
    }
}
