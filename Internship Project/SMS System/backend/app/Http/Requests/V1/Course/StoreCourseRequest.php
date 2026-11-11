<?php

namespace App\Http\Requests\V1\Course;

use App\Enums\CourseStatus;
use App\Enums\CourseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('courses.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'subject_id' => ['nullable', 'integer', Rule::exists('subjects', 'id')],
            'teacher_id' => ['nullable', 'integer', Rule::exists('teachers', 'id')],
            'name' => ['required', 'string', 'max:191'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('courses', 'code')],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::enum(CourseType::class)],
            'fee' => ['required', 'numeric', 'min:0'],
            'capacity' => ['required', 'integer', 'min:0'],
            'status' => ['nullable', Rule::enum(CourseStatus::class)],
            'featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
