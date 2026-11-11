<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $student = $this->student();

        $enrollments = $student->enrollments()
            ->with('course')
            ->orderByDesc('enrolled_at')
            ->get();

        $totalCourses = $enrollments->count();
        $completedCourses = $enrollments->where('status', 'completed')->count();
        $activeLessons = $enrollments->sortByDesc('completion_percentage')->first();
        $certificates = $student->certificates()->count();
        $pendingAssignments = $student->assignmentSubmissions()
            ->where('status', '!=', 'graded')
            ->count();

        return view('student.dashboard', compact(
            'student',
            'enrollments',
            'totalCourses',
            'completedCourses',
            'activeLessons',
            'certificates',
            'pendingAssignments',
        ));
    }

    public function progress()
    {
        $student = $this->student();

        $enrollments = $student->enrollments()
            ->with('course')
            ->get()
            ->each(function ($enrollment) {
                $enrollment->total_lessons = $enrollment->course->publishedLessons()->count();
                $enrollment->completed_lessons = $enrollment->lessonProgress()
                    ->where('is_completed', true)
                    ->count();
            });

        $totalLearningTime = $student->lessonProgress()
            ->sum('learning_time_seconds');

        return view('student.progress', compact('student', 'enrollments', 'totalLearningTime'));
    }

    public function grades()
    {
        $student = $this->student();

        $grades = Grade::where('student_id', $student->id)
            ->with(['course', 'quiz', 'assignment'])
            ->latest()
            ->paginate(20);

        return view('student.grades', compact('student', 'grades'));
    }

    protected function student(): Student
    {
        $student = auth()->user()->studentProfile;

        abort_if(! $student, 404);

        return $student;
    }
}