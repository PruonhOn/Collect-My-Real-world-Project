<?php

namespace App\Http\Requests\V1\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
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
        $teacher = $this->route('teacher');

        return [
            'name' => ['sometimes', 'string', 'max:191'],
            'email' => ['sometimes', 'email', 'max:191', Rule::unique('users', 'email')->ignore($teacher->user_id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
            'employee_code' => ['sometimes', 'string', 'max:50', Rule::unique('teachers', 'employee_code')->ignore($teacher->id)],
            'hire_date' => ['nullable', 'date'],
            'qualification' => ['nullable', 'string', 'max:191'],
            'specialization' => ['nullable', 'string', 'max:191'],
            'bio' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
