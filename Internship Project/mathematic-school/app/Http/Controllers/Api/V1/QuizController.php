<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct(protected QuizService $quizService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->studentProfile;

        if (! $student) {
            return response()->json(['message' => 'រកមិនឃើញសិស្ស។'], 404);
        }

        $courseIds = $student->enrollments()->pluck('course_id');

        $quizzes = Quiz::where('is_published', true)
            ->whereHas('course', fn ($q) => $q->where('status', 'published'))
            ->whereIn('course_id', $courseIds)
            ->with(['course', 'attempts' => fn ($q) => $q->where('student_id', $student->id)])
            ->withCount('questions')
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($quizzes);
    }

    public function start(Request $request, Quiz $quiz): JsonResponse
    {
        $student = $request->user()->studentProfile;

        if (! $student) {
            return response()->json(['message' => 'រកមិនឃើញសិស្ស។'], 404);
        }

        if (! $quiz->is_published) {
            return response()->json(['message' => 'លំហាត់ប្រឡងមិនមានទេ។'], 404);
        }

        $course = $quiz->course;
        if ($course->status !== 'published') {
            return response()->json(['message' => 'វគ្គសិក្សាមិនអាចចូលប្រើបានទេ។'], 404);
        }

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        if (! $enrollment) {
            return response()->json(['message' => 'អ្នកមិនទាន់បានចុះឈ្មោះក្នុងវគ្គសិក្សានេះទេ។'], 403);
        }

        $quiz->load('questions.answers');

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'started_at' => now(),
        ]);

        return response()->json([
            'quiz' => $quiz,
            'attempt' => $attempt,
        ]);
    }

    public function submit(Request $request, Quiz $quiz): JsonResponse
    {
        $student = $request->user()->studentProfile;

        if (! $student) {
            return response()->json(['message' => 'រកមិនឃើញសិស្ស។'], 404);
        }

        $request->validate([
            'answers' => ['nullable', 'array'],
            'attempt_id' => ['required', 'exists:quiz_attempts,id'],
        ]);

        $attempt = QuizAttempt::where('id', $request->attempt_id)
            ->where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->first();

        if (! $attempt) {
            return response()->json(['message' => 'រកមិនឃើញការព្យាយាម។'], 404);
        }

        $attempt = $this->quizService->submitAttempt($quiz->id, $student->id, $request->input('answers', []));

        activity_notify_quiz_result($attempt);

        return response()->json([
            'message' => 'បានដាក់ស្នើដោយជោគជ័យ។',
            'attempt' => $attempt->load('answers.question'),
        ]);
    }

    public function result(Request $request, Quiz $quiz, QuizAttempt $attempt): JsonResponse
    {
        $student = $request->user()->studentProfile;

        if (! $student || $attempt->student_id !== $student->id) {
            return response()->json(['message' => 'អ្នកមិនមានសិទ្ធិចូលប្រើទំព័រនេះទេ។'], 403);
        }

        $attempt->load(['answers.question.answers']);

        return response()->json($attempt);
    }
}
