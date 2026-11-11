<?php

namespace App\Http\Requests\V1\Fee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('fees.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'id')],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')],
            'title' => ['required', 'string', 'max:191'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
