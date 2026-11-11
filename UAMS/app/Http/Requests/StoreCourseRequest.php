<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:courses,code',
            'program_id' => 'required|exists:programs,id',
            'credit_hours' => 'required|integer|min:1|max:6',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Course name is required.',
            'name.string' => 'Course name must be a string.',
            'name.max' => 'Course name must not exceed 255 characters.',
            'code.required' => 'Course code is required.',
            'code.string' => 'Course code must be a string.',
            'code.max' => 'Course code must not exceed 50 characters.',
            'code.unique' => 'A course with this code already exists.',
            'program_id.required' => 'Program is required.',
            'program_id.exists' => 'Selected program does not exist.',
            'credit_hours.required' => 'Credit hours are required.',
            'credit_hours.integer' => 'Credit hours must be an integer.',
            'credit_hours.min' => 'Credit hours must be at least 1.',
            'credit_hours.max' => 'Credit hours must not exceed 6.',
        ];
    }
}
