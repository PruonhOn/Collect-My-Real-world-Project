<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(protected ProgressService $progressService)
    {
    }

    public function show(Request $request, int $courseId, int $lessonId): JsonResponse
    {
        $student = $request->user()->studentProfile;

        if (! $student) {
            return response()->json(['message' => 'រកមិនឃើញសិស្ស។'], 404);
        }

        $lesson = Lesson::where('course_id', $courseId)
            ->where('id', $lessonId)
            ->where('is_published', true)
            ->firstOrFail();

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $courseId)
            ->first();

        if (! $enrollment) {
            return response()->json(['message' => 'អ្នកមិនទាន់បានចុះឈ្មោះក្នុងវគ្គសិក្សានេះទេ។'], 403);
        }

        $lesson->load('materials');

        $isCompleted = $student->lessonProgress()
            ->where('lesson_id', $lesson->id)
            ->where('is_completed', true)
            ->exists();

        return response()->json([
            'lesson' => $lesson,
            'is_completed' => $isCompleted,
        ]);
    }

    public function complete(Request $request, int $lessonId): JsonResponse
    {
        $student = $request->user()->studentProfile;

        if (! $student) {
            return response()->json(['message' => 'រកមិនឃើញសិស្ស។'], 404);
        }

        $lesson = Lesson::findOrFail($lessonId);

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $lesson->course_id)
            ->first();

        if (! $enrollment) {
            return response()->json(['message' => 'អ្នកមិនមានសិទ្ធិចូលប្រើទំព័រនេះទេ។'], 403);
        }

        $this->progressService->completeLesson($student->id, $lesson->id);

        return response()->json([
            'message' => 'បានសម្គាល់ថាបានបញ្ចប់។',
        ]);
    }
}
