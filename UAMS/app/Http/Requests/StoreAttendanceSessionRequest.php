<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_section_id' => 'required|exists:class_sections,id',
            'session_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'grace_period_minutes' => 'nullable|integer|min:0|max:120',
        ];
    }

    public function messages(): array
    {
        return [
            'class_section_id.required' => 'Class section is required.',
            'class_section_id.exists' => 'Selected class section does not exist.',
            'session_date.required' => 'Session date is required.',
            'session_date.date' => 'Session date must be a valid date.',
            'start_time.required' => 'Start time is required.',
            'start_time.date_format' => 'Start time must be in HH:MM format.',
            'end_time.date_format' => 'End time must be in HH:MM format.',
            'end_time.after' => 'End time must be after the start time.',
            'grace_period_minutes.integer' => 'Grace period must be an integer.',
            'grace_period_minutes.min' => 'Grace period must be at least 0 minutes.',
            'grace_period_minutes.max' => 'Grace period must not exceed 120 minutes.',
        ];
    }
}
