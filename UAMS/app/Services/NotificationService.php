<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Notification;
use App\Models\Student;
use Illuminate\Support\Collection;

class NotificationService
{
    public function notifyStudentAbsent(AttendanceRecord $record): void
    {
        $student = $record->student;
        $session = $record->attendanceSession;
        $classSection = $session->classSection;
        $course = $classSection->course;

        Notification::create([
            'user_id' => $student->user_id,
            'type' => 'absence',
            'title' => 'Absence Recorded',
            'message' => "You were marked absent in {$course->name} on {$session->session_date}.",
            'data' => [
                'attendance_record_id' => $record->id,
                'session_id' => $session->id,
                'course_id' => $course->id,
            ],
            'read_at' => null,
        ]);
    }

    public function notifyAbsenceThresholdWarning(Student $student, float $absenceRate): void
    {
        $thresholdSetting = config('uams.absence_threshold', 25);
        $message = "Your absence rate has reached {$absenceRate}%, which exceeds the {$thresholdSetting}% threshold. Please contact your department immediately.";

        Notification::create([
            'user_id' => $student->user_id,
            'type' => 'absence_threshold_warning',
            'title' => 'Attendance Warning',
            'message' => $message,
            'data' => [
                'absence_rate' => $absenceRate,
                'threshold' => $thresholdSetting,
            ],
            'read_at' => null,
        ]);

        $departmentAdmins = $student->department->users
            ->where('role', 'department_admin');

        foreach ($departmentAdmins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'student_absence_threshold_warning',
                'title' => 'Student Attendance Warning',
                'message' => "Student {$student->student_code} ({$student->user->first_name} {$student->user->last_name}) has an absence rate of {$absenceRate}%, exceeding the {$thresholdSetting}% threshold.",
                'data' => [
                    'student_id' => $student->id,
                    'absence_rate' => $absenceRate,
                    'threshold' => $thresholdSetting,
                ],
                'read_at' => null,
            ]);
        }
    }

    public function getUnreadNotifications($user): Collection
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function markAsRead($notificationId, $userId): void
    {
        Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->update(['read_at' => now()]);
    }
}
