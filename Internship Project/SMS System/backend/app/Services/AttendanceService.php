<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class AttendanceService
{
    /**
     * Record or update a single attendance mark. The composite unique index
     * (course_id, student_id, date) keeps one mark per student per day.
     *
     * @param  array{status?: string, remark?: string|null, schedule_id?: int|null}  $data
     */
    public function record(Course $course, Student $student, string $date, array $data, ?int $recordedBy = null): Attendance
    {
        $this->guardStatus($data['status'] ?? AttendanceStatus::Present->value);

        return Attendance::updateOrCreate(
            [
                'course_id' => $course->getKey(),
                'student_id' => $student->getKey(),
                'date' => $date,
            ],
            [
                'schedule_id' => $data['schedule_id'] ?? null,
                'status' => $data['status'] ?? AttendanceStatus::Present->value,
                'remark' => $data['remark'] ?? null,
                'recorded_by' => $recordedBy,
            ],
        );
    }

    /**
     * Bulk record attendance for many students for the same course/date.
     *
     * @param  array<int, array{student_id: int, status?: string, remark?: string|null}>  $rows
     */
    public function recordBulk(Course $course, string $date, array $rows, ?int $recordedBy = null): Collection
    {
        $enrolledStudentIds = $course->students()->pluck('students.id')->all();

        $records = collect($rows)->map(function (array $row) use ($course, $date, $enrolledStudentIds, $recordedBy) {
            if (! in_array((int) $row['student_id'], $enrolledStudentIds, true)) {
                throw ValidationException::withMessages([
                    'rows' => sprintf('Student #%d is not enrolled in this course.', $row['student_id']),
                ]);
            }

            $this->guardStatus($row['status'] ?? AttendanceStatus::Present->value);

            return $this->record(
                $course,
                Student::findOrFail($row['student_id']),
                $date,
                $row,
                $recordedBy,
            );
        });

        return $records;
    }

    /**
     * Percentage of marks with a "present" status for a student's enrollments.
     */
    public function percentageForStudent(int $studentId, ?int $courseId = null, ?string $from = null, ?string $to = null): array
    {
        $query = Attendance::query()->where('student_id', $studentId);

        if ($courseId !== null) {
            $query->where('course_id', $courseId);
        }

        if ($from !== null) {
            $query->where('date', '>=', $from);
        }

        if ($to !== null) {
            $query->where('date', '<=', $to);
        }

        $total = (clone $query)->count();
        $present = (clone $query)->whereIn('status', [
            AttendanceStatus::Present->value,
            AttendanceStatus::Late->value,
        ])->count();
        $late = (clone $query)->where('status', AttendanceStatus::Late->value)->count();
        $absent = (clone $query)->where('status', AttendanceStatus::Absent->value)->count();

        return [
            'total_marks' => $total,
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'percentage' => $total > 0 ? round($present / $total * 100, 2) : 0.0,
        ];
    }

    private function guardStatus(string $status): void
    {
        $allowed = array_column(AttendanceStatus::cases(), 'value');

        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Invalid attendance status: {$status}.",
            ]);
        }
    }
}
