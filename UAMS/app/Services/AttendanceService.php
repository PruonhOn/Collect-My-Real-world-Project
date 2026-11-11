<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassSection;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttendanceService
{
    public function createSession(ClassSection $classSection, array $data): AttendanceSession
    {
        return AttendanceSession::create([
            'class_section_id' => $classSection->id,
            'session_date' => $data['session_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'grace_period_minutes' => $data['grace_period_minutes'] ?? 15,
            'qr_token' => Str::random(64),
            'status' => 'active',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function closeSession(AttendanceSession $session): AttendanceSession
    {
        DB::transaction(function () use ($session) {
            $enrolledStudents = Enrollment::where('class_section_id', $session->class_section_id)
                ->where('status', 'active')
                ->pluck('student_id');

            $markedStudentIds = AttendanceRecord::where('attendance_session_id', $session->id)
                ->whereIn('status', ['present', 'late', 'excused'])
                ->pluck('student_id');

            $unmarkedStudentIds = $enrolledStudents->diff($markedStudentIds);

            foreach ($unmarkedStudentIds as $studentId) {
                AttendanceRecord::create([
                    'attendance_session_id' => $session->id,
                    'student_id' => $studentId,
                    'status' => 'absent',
                    'method' => 'auto',
                    'reason' => null,
                    'marked_by' => null,
                ]);

                AuditLog::create([
                    'auditable_type' => AttendanceRecord::class,
                    'auditable_id' => $session->id,
                    'user_id' => auth()->id(),
                    'action' => 'auto_absent',
                    'old_value' => null,
                    'new_value' => 'absent',
                    'field_changed' => 'status',
                    'description' => "Student {$studentId} auto-marked absent on session close",
                ]);
            }

            $session->update(['status' => 'closed']);
        });

        return $session->fresh();
    }

    public function regenerateQrToken(AttendanceSession $session): AttendanceSession
    {
        $session->update(['qr_token' => Str::random(64)]);

        return $session->fresh();
    }

    public function markAttendance(
        AttendanceSession $session,
        Student $student,
        string $status,
        ?string $reason = null,
        ?int $markedBy = null,
        string $method = 'manual'
    ): AttendanceRecord {
        $existingRecord = AttendanceRecord::where('attendance_session_id', $session->id)
            ->where('student_id', $student->id)
            ->first();

        $oldStatus = $existingRecord?->status;

        $record = AttendanceRecord::updateOrCreate(
            [
                'attendance_session_id' => $session->id,
                'student_id' => $student->id,
            ],
            [
                'status' => $status,
                'reason' => $reason,
                'marked_by' => $markedBy ?? auth()->id(),
                'method' => $method,
            ]
        );

        if ($existingRecord && $oldStatus !== $status) {
            AuditLog::create([
                'auditable_type' => AttendanceRecord::class,
                'auditable_id' => $record->id,
                'user_id' => auth()->id(),
                'action' => 'update',
                'old_value' => $oldStatus,
                'new_value' => $status,
                'field_changed' => 'status',
                'description' => "Attendance status changed from {$oldStatus} to {$status}",
            ]);
        }

        return $record;
    }

    public function bulkMarkAttendance(AttendanceSession $session, array $records, int $markedBy): Collection
    {
        $results = collect();

        foreach ($records as $record) {
            $student = Student::findOrFail($record['student_id']);
            $result = $this->markAttendance(
                $session,
                $student,
                $record['status'],
                $record['reason'] ?? null,
                $markedBy,
                $record['method'] ?? 'manual'
            );
            $results->push($result);
        }

        return $results;
    }

    public function qrCheckIn(string $qrToken, Student $student): AttendanceRecord
    {
        $session = AttendanceSession::where('qr_token', $qrToken)->first();

        if (!$session) {
            throw new \InvalidArgumentException('Invalid QR token. No matching session found.');
        }

        if ($session->status !== 'active') {
            throw new \RuntimeException('This attendance session is no longer active.');
        }

        if (!$session->end_time) {
            return $this->markAttendance($session, $student, 'present', null, null, 'qr');
        }

        $endTimeWithGrace = $session->end_time
            ->copy()
            ->addMinutes($session->grace_period_minutes);

        if (now()->greaterThan($endTimeWithGrace)) {
            throw new \RuntimeException('This QR code has expired. The grace period has ended.');
        }

        return $this->markAttendance($session, $student, 'present', null, null, 'qr');
    }

    public function getSessionStats(AttendanceSession $session): array
    {
        $totalEnrolled = Enrollment::where('class_section_id', $session->class_section_id)
            ->where('status', 'active')
            ->count();

        $records = AttendanceRecord::where('attendance_session_id', $session->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $presentCount = $records->get('present', 0);
        $absentCount = $records->get('absent', 0);
        $lateCount = $records->get('late', 0);
        $excusedCount = $records->get('excused', 0);

        $totalMarked = $presentCount + $lateCount + $excusedCount;
        $attendanceRate = $totalEnrolled > 0 ? ($totalMarked / $totalEnrolled) * 100 : 0;

        return [
            'total_enrolled' => $totalEnrolled,
            'present_count' => $presentCount,
            'absent_count' => $absentCount,
            'late_count' => $lateCount,
            'excused_count' => $excusedCount,
            'attendance_rate' => round($attendanceRate, 2),
        ];
    }

    public function getStudentAttendanceRate(Student $student, ?int $classSectionId = null): float
    {
        $query = AttendanceRecord::where('student_id', $student->id);

        if ($classSectionId) {
            $query->whereHas('attendanceSession', function ($q) use ($classSectionId) {
                $q->where('class_section_id', $classSectionId);
            });
        }

        $totalRecords = (clone $query)->count();

        if ($totalRecords === 0) {
            return 0.0;
        }

        $presentCount = (clone $query)
            ->whereIn('status', ['present', 'late', 'excused'])
            ->count();

        return round(($presentCount / $totalRecords) * 100, 2);
    }
}
