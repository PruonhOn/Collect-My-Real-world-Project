<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $teacher = $this->route('teacher');
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email' . ($teacher?->user_id ? ",{$teacher->user_id}" : ''),
            'department_id' => 'required|exists:departments,id',
            'employee_code' => 'nullable|string|unique:teachers,employee_code' . ($teacher?->id ? ",{$teacher->id}" : ''),
            'password' => ($isUpdate ? 'nullable' : 'required') . '|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Teacher name is required.',
            'name.string' => 'Teacher name must be a string.',
            'name.max' => 'Teacher name must not exceed 255 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'A user with this email address already exists.',
            'department_id.required' => 'Department is required.',
            'department_id.exists' => 'Selected department does not exist.',
            'employee_code.string' => 'Employee code must be a string.',
            'employee_code.unique' => 'A teacher with this employee code already exists.',
            'password.required' => 'Password is required.',
            'password.string' => 'Password must be a string.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
