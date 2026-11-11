<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|integer|exists:students,id',
            'status' => 'required|in:present,absent,late,excused',
            'reason' => 'required_if:status,excused|nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Student is required.',
            'student_id.exists' => 'Selected student does not exist.',
            'status.required' => 'Attendance status is required.',
            'status.in' => 'Status must be one of: present, absent, late, or excused.',
            'reason.required_if' => 'A reason is required when marking attendance as excused.',
            'reason.string' => 'Reason must be a string.',
            'reason.max' => 'Reason must not exceed 500 characters.',
        ];
    }
}
