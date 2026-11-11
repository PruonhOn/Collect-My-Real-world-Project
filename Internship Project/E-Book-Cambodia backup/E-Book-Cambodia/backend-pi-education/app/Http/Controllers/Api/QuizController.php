<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuizController extends Controller
{
    /**
     * Public listing of published quizzes.
     */
    public function index(): JsonResponse
    {
        $quizzes = Quiz::query()
            ->with('course:id,title', 'questions:id,quiz_id,position')
            ->withCount('questions')
            ->published()
            ->latest()
            ->paginate(12);

        return response()->json($quizzes);
    }

    /**
     * Student view of a published quiz. Question answers are hidden; the
     * correct option is only revealed after an attempt is submitted.
     */
    public function show(Request $request, Quiz $quiz): JsonResponse
    {
        abort_unless($quiz->is_published, 404);

        $quiz->load(['course:id,title', 'questions:id,quiz_id,question,options,position']);

        $user = $request->user();
        $attempts = $user
            ? $user->quizAttempts()->where('quiz_id', $quiz->id)->latest()->get()
            : collect();

        return response()->json([
            'quiz' => $quiz,
            'attempts' => $attempts,
        ]);
    }

    /**
     * Submit an attempt and receive an immediate score + review.
     */
    public function attempt(Request $request, Quiz $quiz): JsonResponse
    {
        abort_unless($quiz->is_published, 404);

        $user = $request->user();

        $previousAttempts = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->count();

        if ($previousAttempts >= Quiz::MAX_ATTEMPTS) {
            return response()->json([
                'message' => 'You have used all '.Quiz::MAX_ATTEMPTS.' attempts for this quiz.',
            ], 422);
        }

        $questions = $quiz->questions()->get();

        if ($questions->isEmpty()) {
            return response()->json(['message' => 'This quiz has no questions yet.'], 422);
        }

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer', 'min:0'],
        ]);

        $answers = array_map('intval', $validated['answers']);

        $review = $questions->map(fn (QuizQuestion $question) => [
            'id' => $question->id,
            'question' => $question->question,
            'options' => $question->options,
            'selected' => $answers[$question->id] ?? null,
            'correct_option' => $question->correct_option,
            'is_correct' => ($answers[$question->id] ?? null) === $question->correct_option,
        ]);

        $score = $review->where('is_correct', true)->count();
        $total = $questions->count();
        $passed = $total > 0 && (int) round(($score / $total) * 100) >= $quiz->pass_percent;

        $attempt = QuizAttempt::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'score' => $score,
            'total' => $total,
            'answers' => $answers,
            'passed' => $passed,
            'completed_at' => now(),
        ]);

        return response()->json([
            'attempt' => $attempt,
            'percent' => $attempt->percent(),
            'passed' => $passed,
            'review' => $review,
        ]);
    }

    /**
     * The user's attempts for a quiz.
     */
    public function myAttempts(Request $request, Quiz $quiz): JsonResponse
    {
        $attempts = $request->user()->quizAttempts()
            ->where('quiz_id', $quiz->id)
            ->latest()
            ->get();

        return response()->json(['attempts' => $attempts]);
    }

    // ============================================================
    // Admin
    // ============================================================

    public function adminIndex(): JsonResponse
    {
        $quizzes = Quiz::query()
            ->with('course:id,title')
            ->withCount('questions', 'attempts')
            ->latest()
            ->paginate(15);

        return response()->json($quizzes);
    }

    public function adminShow(Quiz $quiz): JsonResponse
    {
        return response()->json([
            'quiz' => $quiz->load('course:id,title', 'questions'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateQuiz($request);

        $quiz = Quiz::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'course_id' => $validated['course_id'] ?? null,
            'time_limit_seconds' => $validated['time_limit_seconds'] ?? null,
            'pass_percent' => $validated['pass_percent'] ?? 50,
            'is_published' => $validated['is_published'] ?? true,
        ]);

        $this->syncQuestions($quiz, $validated['questions'] ?? []);

        return response()->json(['quiz' => $quiz->load('questions')], 201);
    }

    public function update(Request $request, Quiz $quiz): JsonResponse
    {
        $validated = $this->validateQuiz($request);

        $quiz->update([
            'title' => $validated['title'] ?? $quiz->title,
            'description' => $validated['description'] ?? null,
            'course_id' => $validated['course_id'] ?? null,
            'time_limit_seconds' => $validated['time_limit_seconds'] ?? $quiz->time_limit_seconds,
            'pass_percent' => $validated['pass_percent'] ?? $quiz->pass_percent,
            'is_published' => $validated['is_published'] ?? $quiz->is_published,
        ]);

        if (array_key_exists('questions', $validated)) {
            $quiz->questions()->delete();
            $this->syncQuestions($quiz, $validated['questions']);
        }

        return response()->json(['quiz' => $quiz->fresh()->load('questions')]);
    }

    public function destroy(Quiz $quiz): JsonResponse
    {
        $quiz->delete();

        return response()->json(['message' => 'Quiz deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateQuiz(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')],
            'time_limit_seconds' => ['nullable', 'integer', 'min:30'],
            'pass_percent' => ['nullable', 'integer', 'between:1,100'],
            'is_published' => ['nullable', 'boolean'],
            'questions' => ['sometimes', 'array'],
            'questions.*.question' => ['required', 'string'],
            'questions.*.options' => ['required', 'array', 'min:2', 'max:6'],
            'questions.*.options.*' => ['required', 'string', 'max:500'],
            'questions.*.correct_option' => ['required', 'integer', 'min:0'],
            'questions.*.position' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function syncQuestions(Quiz $quiz, array $questions): void
    {
        foreach ($questions as $index => $question) {
            $quiz->questions()->create([
                'question' => $question['question'],
                'options' => array_values($question['options']),
                'correct_option' => (int) $question['correct_option'],
                'position' => $question['position'] ?? ($index + 1),
            ]);
        }
    }
}
