<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|exists:courses,id',
            'teacher_id' => 'required|exists:teachers,id',
            'term_id' => 'required|exists:academic_terms,id',
            'name' => 'required|string|max:255',
            'room' => 'required|string|max:100',
            'schedule_json' => 'nullable|array',
            'schedule_json.day' => 'required_with:schedule_json|string',
            'schedule_json.time' => 'required_with:schedule_json|string',
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => 'Course is required.',
            'course_id.exists' => 'Selected course does not exist.',
            'teacher_id.required' => 'Teacher is required.',
            'teacher_id.exists' => 'Selected teacher does not exist.',
            'term_id.required' => 'Academic term is required.',
            'term_id.exists' => 'Selected academic term does not exist.',
            'name.required' => 'Section name is required.',
            'name.string' => 'Section name must be a string.',
            'name.max' => 'Section name must not exceed 255 characters.',
            'room.required' => 'Room is required.',
            'room.string' => 'Room must be a string.',
            'room.max' => 'Room must not exceed 100 characters.',
            'schedule_json.array' => 'Schedule must be an array.',
            'schedule_json.day.required_with' => 'Schedule day is required when schedule is provided.',
            'schedule_json.day.string' => 'Schedule day must be a string.',
            'schedule_json.time.required_with' => 'Schedule time is required when schedule is provided.',
            'schedule_json.time.string' => 'Schedule time must be a string.',
        ];
    }
}
