<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Enrollment::with(['student', 'course']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', fn ($sq) => $sq->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('course', fn ($cq) => $cq->where('title', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $enrollments = $query->latest()->paginate(15)->withQueryString();

        return view('admin.enrollments.index', compact('enrollments'));
    }

    public function create()
    {
        $students = Student::orderBy('full_name')->get();
        $courses = Course::orderBy('title')->get();

        return view('admin.enrollments.create', compact('students', 'courses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'status' => ['required', 'in:active,completed,dropped,pending'],
        ]);

        $exists = Enrollment::where('student_id', $data['student_id'])
            ->where('course_id', $data['course_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['student_id' => 'សិស្សនេះបានចុះឈ្មោះក្នុងវគ្គសិក្សានេះរួចហើយ។'])
                ->withInput();
        }

        $data['enrolled_at'] = now();
        $data['completion_percentage'] = 0;

        Enrollment::create($data);

        return redirect()->route('admin.enrollments.index')
            ->with('success', __('messages.enrolled_successfully'));
    }

    public function show(Enrollment $enrollment)
    {
        $enrollment->load(['student', 'course', 'lessonProgress.lesson', 'payment']);

        return view('admin.enrollments.show', compact('enrollment'));
    }

    public function destroy(Enrollment $enrollment)
    {
        $enrollment->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }
}
