<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:programs,code,' . $this->route('program')?->id . ',id',
            'department_id' => 'sometimes|required|exists:departments,id',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Program name is required.',
            'name.string' => 'Program name must be a string.',
            'name.max' => 'Program name must not exceed 255 characters.',
            'code.required' => 'Program code is required.',
            'code.string' => 'Program code must be a string.',
            'code.max' => 'Program code must not exceed 50 characters.',
            'code.unique' => 'A program with this code already exists.',
            'department_id.required' => 'Department is required.',
            'department_id.exists' => 'Selected department does not exist.',
            'description.string' => 'Description must be a string.',
            'description.max' => 'Description must not exceed 1000 characters.',
        ];
    }
}
