<?php

namespace App\Services;

use App\Enums\StudentStatus;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Collection;

final readonly class DashboardService
{
    public function adminStats(): array
    {
        return [
            'total_students' => Student::query()->count(),
            'active_students' => Student::query()->where('status', StudentStatus::Active->value)->count(),
            'total_teachers' => Teacher::query()->count(),
            'total_staff' => Staff::query()->count(),
            'total_courses' => Course::query()->count(),
            'active_courses' => Course::query()->where('status', 'active')->count(),
            'total_enrollments' => CourseEnrollment::query()->count(),
            'attendance_today' => Attendance::query()->where('date', today()->toDateString())->count(),
            'payments_this_month' => (float) Payment::query()
                ->where('status', 'confirmed')
                ->where('paid_on', '>=', now()->startOfMonth())
                ->sum('amount'),
        ];
    }

    /**
     * Chart series over the last N months: enrollments, payments, attendance.
     */
    public function chartSeries(int $months = 6): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth()
            ->replaceDay(1)
            ->startOfDay();
        $labels = [];
        $interval = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $labels[] = $month->format('Y-m');
            $interval[$month->format('Y-m')] = [
                'from' => $month->copy()->startOfMonth(),
                'to' => $month->copy()->endOfMonth(),
            ];
        }

        $enrollments = [];
        $payments = [];
        $attendance = [];

        foreach (array_keys($interval) as $key) {
            $from = $interval[$key]['from'];
            $to = $interval[$key]['to'];

            $enrollments[] = CourseEnrollment::query()
                ->whereBetween('created_at', [$from, $to])
                ->count();
            $payments[] = (float) Payment::query()
                ->where('status', 'confirmed')
                ->whereBetween('paid_on', [$from, $to])
                ->sum('amount');
            $attendance[] = Attendance::query()
                ->whereBetween('created_at', [$from, $to])
                ->count();
        }

        return [
            'months' => $labels,
            'enrollments' => $enrollments,
            'payments' => $payments,
            'attendance' => $attendance,
        ];
    }

    /**
     * Course occupancy breakdown for teacher/admin dashboards.
     *
     * @return array{labels: array<string>, data: array<int>}
     */
    public function courseDistribution(Collection $courses): array
    {
        $labels = $courses->pluck('name')->map(fn (string $n) => mb_strlen($n) > 24 ? mb_substr($n, 0, 24).'…' : $n)->all();
        $data = $courses->map(fn (Course $course) => $course->students_count)->all();

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Teacher dashboard: assigned courses, today's classes, attendance summary.
     */
    public function teacherStats(Teacher $teacher): array
    {
        $courseIds = $teacher->courses()->pluck('courses.id');

        $today = today();
        $dayOfWeek = (int) $today->format('N');

        $courses = Course::query()
            ->withCount('students')
            ->whereIn('id', $courseIds)
            ->get();

        $todaysClasses = Course::query()
            ->whereIn('id', $courseIds)
            ->whereHas('schedules', fn ($q) => $q->where('day_of_week', $dayOfWeek))
            ->with('schedules')
            ->get();

        $attendanceToday = Attendance::query()
            ->whereIn('course_id', $courseIds)
            ->where('date', $today->toDateString())
            ->count();

        return [
            'total_courses' => $courses->count(),
            'total_students' => (int) $courses->sum('students_count'),
            'todays_classes' => $todaysClasses,
            'attendance_marked_today' => $attendanceToday,
        ];
    }
}
