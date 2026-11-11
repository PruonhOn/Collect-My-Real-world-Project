<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Grade;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentController extends Controller
{
    public function children(Request $request): JsonResponse
    {
        $parent = $request->user()->parentProfile;

        if (! $parent) {
            return response()->json(['message' => 'រកមិនឃើញមាតាបិតា។'], 404);
        }

        $children = $parent->children()
            ->with('enrollments.course')
            ->get()
            ->map(function ($child) {
                $total = 0;
                $completed = 0;

                foreach ($child->enrollments as $enrollment) {
                    $total += $enrollment->course->publishedLessons()->count();
                    $completed += $enrollment->lessonProgress()->where('is_completed', true)->count();
                }

                $child->overall_progress = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

                return $child;
            });

        return response()->json($children);
    }

    public function childProgress(Request $request, Student $student): JsonResponse
    {
        $parent = $request->user()->parentProfile;

        if (! $parent || ! $parent->children()->where('students.id', $student->id)->exists()) {
            return response()->json(['message' => 'អ្នកមិនមានសិទ្ធិចូលប្រើទំព័រនេះទេ។'], 403);
        }

        $enrollments = $student->enrollments()
            ->with('course')
            ->get()
            ->map(function ($enrollment) {
                $enrollment->total_lessons = $enrollment->course->publishedLessons()->count();
                $enrollment->completed_lessons = $enrollment->lessonProgress()->where('is_completed', true)->count();

                return $enrollment;
            });

        $totalLearningTime = $student->lessonProgress()->sum('learning_time_seconds');

        return response()->json([
            'student' => $student->only(['id', 'full_name', 'student_id', 'age']),
            'enrollments' => $enrollments,
            'total_learning_time' => $totalLearningTime,
        ]);
    }

    public function childResults(Request $request, Student $student): JsonResponse
    {
        $parent = $request->user()->parentProfile;

        if (! $parent || ! $parent->children()->where('students.id', $student->id)->exists()) {
            return response()->json(['message' => 'អ្នកមិនមានសិទ្ធិចូលប្រើទំព័រនេះទេ។'], 403);
        }

        $quizAttempts = $student->quizAttempts()
            ->with('quiz.course')
            ->latest()
            ->paginate($request->get('per_page', 15));

        $submissions = $student->assignmentSubmissions()
            ->with('assignment.course')
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'quiz_attempts' => $quizAttempts,
            'submissions' => $submissions,
        ]);
    }

    public function childCertificates(Request $request, Student $student): JsonResponse
    {
        $parent = $request->user()->parentProfile;

        if (! $parent || ! $parent->children()->where('students.id', $student->id)->exists()) {
            return response()->json(['message' => 'អ្នកមិនមានសិទ្ធិចូលប្រើទំព័រនេះទេ។'], 403);
        }

        $certificates = $student->certificates()
            ->with('course')
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($certificates);
    }

    public function childPayments(Request $request, Student $student): JsonResponse
    {
        $parent = $request->user()->parentProfile;

        if (! $parent || ! $parent->children()->where('students.id', $student->id)->exists()) {
            return response()->json(['message' => 'អ្នកមិនមានសិទ្ធិចូលប្រើទំព័រនេះទេ។'], 403);
        }

        $payments = $student->payments()
            ->with('course')
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($payments);
    }
}
