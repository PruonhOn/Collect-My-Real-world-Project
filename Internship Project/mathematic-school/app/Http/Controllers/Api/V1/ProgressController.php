<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Grade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->studentProfile;

        if (! $student) {
            return response()->json(['message' => 'រកមិនឃើញសិស្ស។'], 404);
        }

        $enrollments = $student->enrollments()
            ->with('course')
            ->get()
            ->map(function ($enrollment) {
                $enrollment->total_lessons = $enrollment->course->publishedLessons()->count();
                $enrollment->completed_lessons = $enrollment->lessonProgress()
                    ->where('is_completed', true)
                    ->count();

                return $enrollment;
            });

        $totalLearningTime = $student->lessonProgress()->sum('learning_time_seconds');

        $totalCourses = $enrollments->count();
        $completedCourses = $enrollments->where('status', 'completed')->count();
        $certificates = $student->certificates()->count();

        return response()->json([
            'enrollments' => $enrollments,
            'total_learning_time' => $totalLearningTime,
            'summary' => [
                'total_courses' => $totalCourses,
                'completed_courses' => $completedCourses,
                'certificates' => $certificates,
            ],
        ]);
    }

    public function grades(Request $request): JsonResponse
    {
        $student = $request->user()->studentProfile;

        if (! $student) {
            return response()->json(['message' => 'រកមិនឃើញសិស្ស។'], 404);
        }

        $grades = Grade::where('student_id', $student->id)
            ->with(['course', 'quiz', 'assignment'])
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json($grades);
    }
}
