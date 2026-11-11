<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $teacher = auth()->user()->teacherProfile;

        $courses = $teacher->courses()
            ->withCount('enrollments')
            ->get();

        $totalEnrollments = $courses->sum('enrollments_count');

        $courseIds = $courses->pluck('id');

        $totalStudents = \App\Models\Enrollment::whereIn('course_id', $courseIds)
            ->distinct('student_id')
            ->count('student_id');

        $pendingSubmissions = AssignmentSubmission::whereHas('assignment', function ($q) use ($courseIds) {
            $q->whereIn('course_id', $courseIds);
        })->where('status', '!=', 'graded')->count();

        return view('teacher.dashboard', compact(
            'teacher',
            'courses',
            'totalEnrollments',
            'totalStudents',
            'pendingSubmissions',
            'courseIds'
        ));
    }
}
