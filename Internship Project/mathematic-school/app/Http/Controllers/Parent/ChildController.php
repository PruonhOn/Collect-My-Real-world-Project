<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\ProgressService;

class ChildController extends Controller
{
    public function __construct(protected ProgressService $progressService)
    {
    }

    protected function resolveChild(Student $student): Student
    {
        $parent = auth()->user()->parentProfile;

        $linked = $parent->children()->where('students.id', $student->id)->exists();

        abort_unless($linked, 403, __('messages.unauthorized'));

        return $student->load('user');
    }

    public function index()
    {
        $parent = auth()->user()->parentProfile;

        $children = $parent->children()
            ->with('enrollments.course')
            ->get()
            ->each(function ($child) {
                $total = 0;
                $completed = 0;

                foreach ($child->enrollments as $enrollment) {
                    $total += $enrollment->course->publishedLessons()->count();
                    $completed += $enrollment->lessonProgress()->where('is_completed', true)->count();
                }

                $child->overall_progress = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
            });

        return view('parent.children.index', compact('children'));
    }

    public function show(Student $student)
    {
        $this->resolveChild($student);

        $student->load([
            'enrollments.course.teachers',
            'lessonProgress.lesson',
            'quizAttempts.quiz',
            'assignmentSubmissions.assignment',
            'certificates.course',
            'attendance',
        ]);

        return view('parent.children.show', compact('student'));
    }

    public function progress(Student $student)
    {
        $this->resolveChild($student);

        $enrollments = $student->enrollments()
            ->with('course')
            ->get()
            ->each(function ($enrollment) {
                $enrollment->total_lessons = $enrollment->course->publishedLessons()->count();
                $enrollment->completed_lessons = $enrollment->lessonProgress()->where('is_completed', true)->count();
            });

        $totalLearningTime = $student->lessonProgress()->sum('learning_time_seconds');

        return view('parent.children.progress', compact('student', 'enrollments', 'totalLearningTime'));
    }

    public function results(Student $student)
    {
        $this->resolveChild($student);

        $quizAttempts = $student->quizAttempts()
            ->with('quiz.course')
            ->latest()
            ->paginate(15);

        $submissions = $student->assignmentSubmissions()
            ->with('assignment.course')
            ->latest()
            ->paginate(15);

        return view('parent.children.results', compact('student', 'quizAttempts', 'submissions'));
    }

    public function certificates(Student $student)
    {
        $this->resolveChild($student);

        $certificates = $student->certificates()
            ->with('course')
            ->latest()
            ->paginate(15);

        return view('parent.children.certificates', compact('student', 'certificates'));
    }

    public function payments(Student $student)
    {
        $this->resolveChild($student);

        $payments = $student->payments()
            ->with('course')
            ->latest()
            ->paginate(15);

        return view('parent.children.payments', compact('student', 'payments'));
    }

    public function attendance(Student $student)
    {
        $this->resolveChild($student);

        $attendance = $student->attendance()
            ->with(['course', 'lesson'])
            ->latest()
            ->paginate(15);

        $present = $student->attendance()->where('status', 'present')->count();
        $absent = $student->attendance()->where('status', 'absent')->count();
        $total = $student->attendance()->count();

        return view('parent.children.attendance', compact('student', 'attendance', 'present', 'absent', 'total'));
    }
}