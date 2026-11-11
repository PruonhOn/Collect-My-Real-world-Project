<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ParentGuardian;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalStudents = Student::count();
        $totalParents = ParentGuardian::count();
        $totalTeachers = Teacher::count();
        $totalCourses = Course::count();
        $totalEnrollments = Enrollment::count();
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');

        $recentEnrollments = Enrollment::with('student', 'course')
            ->latest()
            ->take(8)
            ->get();

        $topCourses = Course::withCount('enrollments')
            ->orderByDesc('enrollments_count')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalStudents',
            'totalParents',
            'totalTeachers',
            'totalCourses',
            'totalEnrollments',
            'totalRevenue',
            'recentEnrollments',
            'topCourses',
        ));
    }
}
