<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public function index()
    {
        $user = Auth::user();
        $teacher = $user->teacher;

        abort_unless($teacher, 404);

        $activeTerm = AcademicTerm::where('is_active', true)->first();

        $classSections = $teacher->classSections()
            ->when($activeTerm, fn ($q) => $q->where('term_id', $activeTerm->id))
            ->with(['course', 'academicTerm', 'enrollments'])
            ->get();

        $todaySessions = $teacher->attendanceSessions()
            ->whereDate('attendance_sessions.created_at', today())
            ->with(['classSection.course', 'classSection'])
            ->latest()
            ->get();

        $recentSummaries = collect();

        foreach ($classSections as $section) {
            $totalSessions = $section->attendanceSessions()->count();
            $totalStudents = $section->enrollments()->where('status', 'active')->count();
            $presentCount = $section->attendanceRecords()
                ->where('attendance_records.status', 'present')
                ->count();

            $recentSummaries->push([
                'section' => $section,
                'total_sessions' => $totalSessions,
                'total_students' => $totalStudents,
                'attendance_rate' => $totalSessions > 0 && $totalStudents > 0
                    ? round(($presentCount / ($totalSessions * $totalStudents)) * 100, 1)
                    : 0,
            ]);
        }

        $sectionIds = $classSections->pluck('id');

        $totalStudents = Enrollment::whereIn('class_section_id', $sectionIds)
            ->where('status', 'active')
            ->distinct('student_id')
            ->count('student_id');

        $recordsQuery = AttendanceRecord::whereHas('attendanceSession', fn ($q) => $q->whereIn('class_section_id', $sectionIds));
        $totalRecords = (clone $recordsQuery)->count();
        $presentTotal = (clone $recordsQuery)->where('status', 'present')->count();
        $overallAttendanceRate = $totalRecords > 0 ? round(($presentTotal / $totalRecords) * 100, 1) : 0;

        return view('teacher.dashboard', [
            'teacher' => $teacher,
            'activeTerm' => $activeTerm,
            'classSections' => $classSections,
            'sections' => $classSections,
            'todaySessions' => $todaySessions,
            'recentSummaries' => $recentSummaries,
            'totalStudents' => $totalStudents,
            'overallAttendanceRate' => $overallAttendanceRate,
        ]);
    }
}
