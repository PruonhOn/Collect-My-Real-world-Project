<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'faculty_id' => 'sometimes|required|exists:faculties,id',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Department name is required.',
            'name.string' => 'Department name must be a string.',
            'name.max' => 'Department name must not exceed 255 characters.',
            'faculty_id.required' => 'Faculty is required.',
            'faculty_id.exists' => 'Selected faculty does not exist.',
            'description.string' => 'Description must be a string.',
            'description.max' => 'Description must not exceed 1000 characters.',
        ];
    }
}
