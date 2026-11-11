<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AcademicTerm;
use App\Services\AttendanceService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
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

        $attendanceByCourse = collect();

        foreach ($enrollments as $enrollment) {
            $section = $enrollment->classSection;
            $totalSessions = $section->attendanceSessions()->count();

            $records = $student->attendanceRecords()
                ->whereHas('attendanceSession', fn ($q) => $q->where('class_section_id', $section->id))
                ->with('attendanceSession')
                ->get();

            $presentCount = $records->where('status', 'present')->count();
            $lateCount = $records->where('status', 'late')->count();
            $absentCount = $records->where('status', 'absent')->count();
            $excusedCount = $records->where('status', 'excused')->count();
            $unmarkedCount = $totalSessions - $records->count();

            $attendanceByCourse->push([
                'enrollment' => $enrollment,
                'section' => $section,
                'course' => $section->course,
                'total_sessions' => $totalSessions,
                'present_count' => $presentCount,
                'late_count' => $lateCount,
                'absent_count' => $absentCount,
                'excused_count' => $excusedCount,
                'unmarked_count' => max(0, $unmarkedCount),
                'attendance_rate' => $totalSessions > 0
                    ? round(($presentCount + $lateCount) / $totalSessions * 100, 1)
                    : 0,
            ]);
        }

        return view('student.attendance.index', compact('student', 'enrollments', 'activeTerm'));
    }

    public function show(AttendanceRecord $attendanceRecord)
    {
        $user = Auth::user();
        $student = $user->student;

        abort_unless($student, 404);
        abort_unless($attendanceRecord->student_id === $student->id, 403);

        $attendanceRecord->load([
            'attendanceSession.classSection.course',
            'attendanceSession.classSection.teacher.user',
        ]);

        return view('student.attendance.show', compact('student', 'attendanceRecord'));
    }

    public function qrCheckIn(string $token, AttendanceService $attendanceService)
    {
        $user = Auth::user();
        $student = $user->student;

        abort_unless($student, 404);

        try {
            $attendanceService->qrCheckIn($token, $student);

            return redirect()
                ->route('student.attendance.index')
                ->with('success', 'QR check-in successful! Your attendance has been recorded.');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Check-in failed. Please try again or contact your instructor.');
        }
    }

    public function report(ReportService $reportService)
    {
        $user = Auth::user();
        $student = $user->student;

        abort_unless($student, 404);

        $reportData = $reportService->studentReport($student);

        $activeTerm = AcademicTerm::where('is_active', true)->first();

        $totalSessions = array_sum(array_column($reportData, 'total_sessions'));
        $attendedSessions = 0;
        foreach ($reportData as $row) {
            $attendedSessions += $row['present'] + $row['late'] + $row['excused'];
        }
        $overallRate = $totalSessions > 0 ? round(($attendedSessions / $totalSessions) * 100, 1) : 0;

        return view('student.attendance.report', compact('student', 'reportData', 'activeTerm', 'overallRate'));
    }
}
