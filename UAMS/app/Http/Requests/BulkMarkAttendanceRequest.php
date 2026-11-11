<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkMarkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status' => 'required|in:present,absent,late,excused',
            'records.*.reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'records.required' => 'At least one attendance record is required.',
            'records.array' => 'Records must be an array.',
            'records.min' => 'At least one attendance record is required.',
            'records.*.student_id.required' => 'Student ID is required for each record.',
            'records.*.student_id.exists' => 'One or more selected students do not exist.',
            'records.*.status.required' => 'Attendance status is required for each record.',
            'records.*.status.in' => 'Status must be one of: present, absent, late, or excused.',
            'records.*.reason.string' => 'Reason must be a string.',
            'records.*.reason.max' => 'Reason must not exceed 500 characters.',
        ];
    }
}
