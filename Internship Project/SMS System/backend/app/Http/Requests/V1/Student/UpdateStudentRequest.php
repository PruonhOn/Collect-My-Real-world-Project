<?php

namespace App\Http\Requests\V1\Student;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('students.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $student = $this->route('student');

        return [
            'name' => ['sometimes', 'string', 'max:191'],
            'email' => ['sometimes', 'email', 'max:191', Rule::unique('users', 'email')->ignore($student->user_id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
            'student_code' => ['sometimes', 'string', 'max:50', Rule::unique('students', 'student_code')->ignore($student->id)],
            'khmer_name' => ['nullable', 'string', 'max:191'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'guardian_name' => ['nullable', 'string', 'max:191'],
            'guardian_phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:191'],
            'status' => ['nullable', Rule::enum(StudentStatus::class)],
            'enrolled_on' => ['nullable', 'date'],
        ];
    }
}
