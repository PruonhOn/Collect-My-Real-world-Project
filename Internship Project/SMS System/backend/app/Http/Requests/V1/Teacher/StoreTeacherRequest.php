<?php

namespace App\Http\Requests\V1\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('teachers.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('teachers', 'employee_code')],
            'hire_date' => ['nullable', 'date'],
            'qualification' => ['nullable', 'string', 'max:191'],
            'specialization' => ['nullable', 'string', 'max:191'],
            'bio' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
