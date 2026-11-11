<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\CertificateService;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $student = auth()->user()->studentProfile;

        $query = Course::with(['category', 'teachers'])
            ->withCount('enrollments')
            ->where('status', 'published');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('age_group')) {
            $query->where('age_group', $request->age_group);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        $enrolledIds = $student
            ? $student->enrollments()->pluck('course_id')->all()
            : [];

        $courses = $query->latest()->paginate(9)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $ageGroups = age_groups();

        return view('student.courses.index', compact(
            'courses',
            'categories',
            'ageGroups',
            'enrolledIds',
        ));
    }

    public function show(Course $course)
    {
        abort_unless($course->status === 'published', 404);

        $student = auth()->user()->studentProfile;
        $course->load(['category', 'teachers', 'lessons' => function ($q) {
            $q->where('is_published', true)->orderBy('order');
        }, 'assignments' => function ($q) {
            $q->where('is_published', true);
        }, 'quizzes' => function ($q) {
            $q->where('is_published', true);
        }]);

        $enrollment = $student
            ? Enrollment::where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->first()
            : null;

        $completedLessonIds = $student
            ? $student->lessonProgress()
                ->whereHas('lesson', fn ($q) => $q->where('course_id', $course->id))
                ->where('is_completed', true)
                ->pluck('lesson_id')
                ->all()
            : [];

        return view('student.courses.show', compact('course', 'enrollment', 'completedLessonIds', 'student'));
    }

    public function enroll(Course $course)
    {
        abort_unless($course->status === 'published', 404);

        $student = auth()->user()->studentProfile;
        abort_unless($student, 404);

        $exists = Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->exists();

        if ($exists) {
            return back()->with('info', __('messages.enrolled'));
        }

        Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'status' => 'active',
            'completion_percentage' => 0,
        ]);

        return redirect()->route('student.courses.show', $course)
            ->with('success', __('messages.enrolled_successfully'));
    }

    public function myCourses()
    {
        $student = auth()->user()->studentProfile;
        abort_unless($student, 404);

        $enrollments = $student->enrollments()
            ->with('course', fn ($q) => $q->with(['category', 'teachers']))
            ->orderByDesc('enrolled_at')
            ->paginate(9);

        return view('student.courses.mine', compact('enrollments'));
    }
}