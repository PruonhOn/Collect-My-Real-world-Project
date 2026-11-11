<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassSection;
use App\Models\Enrollment;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceRecordSeeder extends Seeder
{
    public function run(): void
    {
        $closedSessions = AttendanceSession::where('status', 'closed')->get();

        foreach ($closedSessions as $session) {
            $section = ClassSection::find($session->class_section_id);
            $teacher = Teacher::find($section->teacher_id);
            $teacherUser = User::find($teacher->user_id);

            $enrollments = Enrollment::where('class_section_id', $section->id)
                ->where('status', 'active')
                ->get();

            $sessionDate = Carbon::parse($session->session_date);

            foreach ($enrollments as $enrollment) {
                $attendanceStatus = $this->getRandomAttendanceStatus();
                $method = rand(1, 10) <= 7 ? 'manual' : 'qr';

                // Generate a realistic marked_at time
                $startTime = Carbon::parse($session->start_time);
                $markedAt = $sessionDate->copy()->setTime(
                    $startTime->hour,
                    $startTime->minute
                );

                if ($attendanceStatus === 'late') {
                    $markedAt->addMinutes(rand(16, 30));
                } elseif ($attendanceStatus === 'present') {
                    $markedAt->addMinutes(rand(0, 14));
                } else {
                    // absent or excused - mark at end of session
                    $endTime = Carbon::parse($session->end_time);
                    $markedAt->setTime($endTime->hour, $endTime->minute);
                }

                $reason = null;
                if ($attendanceStatus === 'excused') {
                    $reason = $this->getRandomExcuseReason();
                }

                AttendanceRecord::create([
                    'attendance_session_id' => $session->id,
                    'student_id' => $enrollment->student_id,
                    'status' => $attendanceStatus,
                    'marked_by' => $teacherUser->id,
                    'marked_at' => $markedAt,
                    'method' => $method,
                    'reason' => $reason,
                ]);
            }
        }
    }

    private function getRandomAttendanceStatus(): string
    {
        $rand = rand(1, 100);

        if ($rand <= 75) {
            return 'present';
        } elseif ($rand <= 88) {
            return 'absent';
        } elseif ($rand <= 95) {
            return 'late';
        } else {
            return 'excused';
        }
    }

    private function getRandomExcuseReason(): string
    {
        $reasons = [
            'Medical appointment',
            'Family emergency',
            'University approved absence',
            'Religious observance',
            'Academic conflict',
            'Documented illness',
            'Official university business',
        ];

        return $reasons[array_rand($reasons)];
    }
}
