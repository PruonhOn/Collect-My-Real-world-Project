<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\AttendanceSession;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('permission:manage-system');

        $stats = Cache::remember('admin_dashboard_stats', 300, function () {
            return [
                'total_students' => Student::count(),
                'total_teachers' => Teacher::count(),
                'total_courses' => Course::count(),
                'total_sections' => ClassSection::count(),
            ];
        });

        $activeTerm = AcademicTerm::where('is_active', true)->first();

        $recentSessions = AttendanceSession::with(['classSection.course', 'classSection.teacher'])
            ->latest()
            ->limit(10)
            ->get();

        $attendanceRateData = Cache::remember('admin_attendance_rate_7days', 300, function () {
            $days = collect();
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                $totalSessions = AttendanceSession::whereDate('session_date', $date)->count();
                $totalPresent = DB::table('attendance_records')
                    ->whereDate('created_at', $date)
                    ->where('status', 'present')
                    ->count();
                $totalRecords = DB::table('attendance_records')
                    ->whereDate('created_at', $date)
                    ->count();

                $days->push([
                    'date' => $date,
                    'rate' => $totalRecords > 0 ? round(($totalPresent / $totalRecords) * 100, 1) : 0,
                    'sessions' => $totalSessions,
                ]);
            }
            return $days;
        });

        $activeSections = ClassSection::whereHas('term', function ($q) {
            $q->where('is_active', true);
        })->count();

        return view('admin.dashboard', [
            'totalStudents' => $stats['total_students'],
            'totalTeachers' => $stats['total_teachers'],
            'totalCourses' => $stats['total_courses'],
            'activeSections' => $activeSections,
            'activeTerm' => $activeTerm,
            'recentSessions' => $recentSessions,
            'attendanceRateData' => $attendanceRateData,
        ]);
    }
}
