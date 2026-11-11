<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public function index()
    {
        $user = Auth::user();
        $student = $user->student;

        abort_unless($student, 404);

        $activeTerm = AcademicTerm::where('is_active', true)->first();

        $enrollments = $student->enrollments()
            ->when($activeTerm, fn ($q) => $q->whereHas('classSection', fn ($sq) => $sq->where('term_id', $activeTerm->id)))
            ->with(['classSection.course', 'classSection.teacher.user'])
            ->get();

        $totalRecords = $student->attendanceRecords()->count();
        $presentCount = $student->attendanceRecords()->where('status', 'present')->count();
        $lateCount = $student->attendanceRecords()->where('status', 'late')->count();

        $overallAttendanceRate = $totalRecords > 0
            ? round(($presentCount + $lateCount) / $totalRecords * 100, 1)
            : 0;

        $recentRecords = $student->attendanceRecords()
            ->with(['attendanceSession.classSection.course'])
            ->latest()
            ->take(10)
            ->get();

        $warnings = collect();

        foreach ($enrollments as $enrollment) {
            $section = $enrollment->classSection;
            $totalSectionSessions = $section->attendanceSessions()->count();

            if ($totalSectionSessions >= 3) {
                $studentAbsent = $student->attendanceRecords()
                    ->whereHas('attendanceSession', fn ($q) => $q->where('class_section_id', $section->id))
                    ->where('status', 'absent')
                    ->count();

                $absenceRate = ($studentAbsent / $totalSectionSessions) * 100;

                if ($absenceRate >= 20) {
                    $warnings->push([
                        'course_name' => $section->course->name ?? 'N/A',
                        'section_name' => $section->name ?? 'N/A',
                        'message' => "You have been absent for {$studentAbsent} out of {$totalSectionSessions} sessions (" . round($absenceRate, 1) . "%).",
                        'section' => $section,
                        'absent_count' => $studentAbsent,
                        'total_sessions' => $totalSectionSessions,
                        'absence_rate' => round($absenceRate, 1),
                    ]);
                }
            }
        }

        return view('student.dashboard', compact(
            'student',
            'activeTerm',
            'enrollments',
            'overallAttendanceRate',
            'recentRecords',
            'warnings'
        ));
    }
}
