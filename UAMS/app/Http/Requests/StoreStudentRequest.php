<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $student = $this->route('student');
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email' . ($student?->user_id ? ",{$student->user_id}" : ''),
            'student_code' => 'required|string|unique:students,student_code' . ($student?->id ? ",{$student->id}" : ''),
            'department_id' => 'required|exists:departments,id',
            'program_id' => 'required|exists:programs,id',
            'year' => 'required|integer|min:1|max:8',
            'password' => ($isUpdate ? 'nullable' : 'required') . '|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Student name is required.',
            'name.string' => 'Student name must be a string.',
            'name.max' => 'Student name must not exceed 255 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'A user with this email address already exists.',
            'student_code.required' => 'Student code is required.',
            'student_code.string' => 'Student code must be a string.',
            'student_code.unique' => 'A student with this code already exists.',
            'department_id.required' => 'Department is required.',
            'department_id.exists' => 'Selected department does not exist.',
            'program_id.required' => 'Program is required.',
            'program_id.exists' => 'Selected program does not exist.',
            'year.required' => 'Year level is required.',
            'year.integer' => 'Year level must be an integer.',
            'year.min' => 'Year level must be at least 1.',
            'year.max' => 'Year level must not exceed 8.',
            'password.required' => 'Password is required.',
            'password.string' => 'Password must be a string.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
