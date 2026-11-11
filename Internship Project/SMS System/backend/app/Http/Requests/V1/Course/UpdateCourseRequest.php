<?php

namespace App\Http\Requests\V1\Course;

use App\Enums\CourseStatus;
use App\Enums\CourseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
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
        $course = $this->route('course');

        return [
            'academic_year_id' => ['sometimes', 'integer', Rule::exists('academic_years', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'subject_id' => ['nullable', 'integer', Rule::exists('subjects', 'id')],
            'teacher_id' => ['nullable', 'integer', Rule::exists('teachers', 'id')],
            'name' => ['sometimes', 'string', 'max:191'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('courses', 'code')->ignore($course->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['sometimes', Rule::enum(CourseType::class)],
            'fee' => ['sometimes', 'numeric', 'min:0'],
            'capacity' => ['sometimes', 'integer', 'min:0'],
            'status' => ['nullable', Rule::enum(CourseStatus::class)],
            'featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
