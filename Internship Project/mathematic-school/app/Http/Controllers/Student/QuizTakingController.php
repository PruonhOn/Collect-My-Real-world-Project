<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use Illuminate\Http\Request;

class QuizTakingController extends Controller
{
    public function __construct(protected QuizService $quizService)
    {
    }

    public function start(Quiz $quiz)
    {
        $student = auth()->user()->studentProfile;

        $this->authorizeQuiz($quiz, $student->id);

        $quiz->load('questions.answers');

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'started_at' => now(),
        ]);

        return view('student.quizzes.take', compact('quiz', 'attempt'));
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $student = auth()->user()->studentProfile;

        $this->authorizeQuiz($quiz, $student->id);

        $request->validate([
            'answers' => ['nullable', 'array'],
            'attempt_id' => ['required', 'exists:quiz_attempts,id'],
        ]);

        $attempt = QuizAttempt::where('id', $request->attempt_id)
            ->where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $attempt = $this->quizService->submitAttempt($quiz->id, $student->id, $request->input('answers', []));

        activity_notify_quiz_result($attempt);

        return redirect()->route('student.quizzes.result', [$quiz, $attempt])
            ->with('success', __('messages.submitted_successfully'));
    }

    public function result(Quiz $quiz, QuizAttempt $attempt)
    {
        $student = auth()->user()->studentProfile;

        abort_unless($attempt->student_id === $student->id, 403, __('messages.unauthorized'));

        $attempt->load(['answers.question']);

        return view('student.quizzes.result', compact('quiz', 'attempt'));
    }

    protected function authorizeQuiz(Quiz $quiz, int $studentId): void
    {
        abort_unless($quiz->is_published, 404);

        $course = $quiz->course;
        abort_unless($course->status === 'published', 404);

        $enrollment = Enrollment::where('student_id', $studentId)
            ->where('course_id', $course->id)
            ->first();

        abort_unless($enrollment, 403, __('messages.unauthorized'));
    }
}