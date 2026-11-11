<?php

namespace App\Http\Requests\V1\Attendance;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['attendance.record', 'attendance.record.own']) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'schedule_id' => ['nullable', 'integer', Rule::exists('schedules', 'id')],
            'date' => ['required', 'date'],
            'status' => ['required', Rule::enum(AttendanceStatus::class)],
            'remark' => ['nullable', 'string', 'max:191'],
        ];
    }
}
