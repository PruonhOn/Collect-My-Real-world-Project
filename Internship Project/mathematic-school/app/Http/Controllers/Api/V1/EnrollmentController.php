<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->studentProfile;

        if (! $student) {
            return response()->json(['message' => 'រកមិនឃើញសិស្ស។'], 404);
        }

        $enrollments = $student->enrollments()
            ->with(['course.category', 'course.teachers'])
            ->orderByDesc('enrolled_at')
            ->paginate($request->get('per_page', 15));

        return response()->json($enrollments);
    }

    public function store(Request $request): JsonResponse
    {
        $student = $request->user()->studentProfile;

        if (! $student) {
            return response()->json(['message' => 'រកមិនឃើញសិស្ស។'], 404);
        }

        $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
        ]);

        $course = Course::findOrFail($request->course_id);

        if ($course->status !== 'published') {
            return response()->json(['message' => 'វគ្គសិក្សាមិនអាចចុះឈ្មោះបានទេ។'], 422);
        }

        $exists = Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'សិស្សបានចុះឈ្មោះក្នុងវគ្គសិក្សានេះរួចហើយ។'], 422);
        }

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'status' => 'active',
            'completion_percentage' => 0,
        ]);

        return response()->json([
            'message' => 'បានចុះឈ្មោះរៀនដោយជោគជ័យ។',
            'enrollment' => $enrollment->load('course'),
        ], 201);
    }

    public function show(Enrollment $enrollment): JsonResponse
    {
        $student = $request()->user()->studentProfile;

        if (! $student || $enrollment->student_id !== $student->id) {
            return response()->json(['message' => 'អ្នកមិនមានសិទ្ធិចូលប្រើទំព័រនេះទេ។'], 403);
        }

        $enrollment->load(['course.category', 'course.teachers', 'lessonProgress.lesson']);

        return response()->json($enrollment);
    }
}
