<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    protected function resolveCourse(Course $course)
    {
        if (! $course->teachers()->where('teacher_id', auth()->user()->teacherProfile->id)->exists()) {
            abort(403, __('messages.unauthorized'));
        }

        return $course;
    }

    public function index(Course $course)
    {
        $this->resolveCourse($course);

        $quizzes = $course->quizzes()
            ->withCount('questions')
            ->get()
            ->each(function ($quiz) {
                $quiz->total_marks = $quiz->questions()->sum('marks');
            });

        return view('teacher.quizzes.index', compact('course', 'quizzes'));
    }

    public function create(Course $course)
    {
        $this->resolveCourse($course);

        $lessons = $course->lessons()->get();

        return view('teacher.quizzes.create', compact('course', 'lessons'));
    }

    public function store(Request $request, Course $course)
    {
        $this->resolveCourse($course);

        $data = $request->validate([
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'time_limit' => ['nullable', 'integer', 'min:1'],
            'passing_score' => ['nullable', 'numeric', 'min:0'],
            'allow_retry' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'questions' => ['nullable', 'array'],
        ]);

        $data['allow_retry'] = $request->boolean('allow_retry');
        $data['is_published'] = $request->boolean('is_published');

        $quiz = $course->quizzes()->create($data);

        $this->saveQuestions($quiz, $request->input('questions', []));

        return redirect()->route('teacher.quizzes.index', $course)
            ->with('success', __('messages.created_successfully'));
    }

    public function edit(Course $course, Quiz $quiz)
    {
        $this->resolveCourse($course);

        $quiz->load('questions.answers');
        $lessons = $course->lessons()->get();

        return view('teacher.quizzes.edit', compact('course', 'quiz', 'lessons'));
    }

    public function update(Request $request, Course $course, Quiz $quiz)
    {
        $this->resolveCourse($course);

        $data = $request->validate([
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'time_limit' => ['nullable', 'integer', 'min:1'],
            'passing_score' => ['nullable', 'numeric', 'min:0'],
            'allow_retry' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'questions' => ['nullable', 'array'],
        ]);

        $data['allow_retry'] = $request->boolean('allow_retry');
        $data['is_published'] = $request->boolean('is_published');

        $quiz->update($data);

        $quiz->questions()->delete();

        $this->saveQuestions($quiz, $request->input('questions', []));

        return redirect()->route('teacher.quizzes.index', $course)
            ->with('success', __('messages.updated_successfully'));
    }

    public function destroy(Course $course, Quiz $quiz)
    {
        $this->resolveCourse($course);

        $quiz->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }

    public function results(Quiz $quiz)
    {
        if (! $quiz->course->teachers()->where('teacher_id', auth()->user()->teacherProfile->id)->exists()) {
            abort(403, __('messages.unauthorized'));
        }

        $attempts = $quiz->attempts()
            ->with('student')
            ->latest()
            ->paginate(15);

        return view('teacher.quizzes.results', compact('quiz', 'attempts'));
    }

    protected function saveQuestions(Quiz $quiz, array $questions)
    {
        $order = 1;

        foreach ($questions as $questionData) {
            if (empty($questionData['question'])) {
                continue;
            }

            $question = $quiz->questions()->create([
                'question' => $questionData['question'],
                'question_type' => $questionData['type'] ?? 'single',
                'marks' => $questionData['marks'] ?? 1,
                'order' => $order++,
            ]);

            foreach ($questionData['answers'] ?? [] as $answerData) {
                if (empty($answerData['answer'])) {
                    continue;
                }

                $question->answers()->create([
                    'answer' => $answerData['answer'],
                    'is_correct' => isset($answerData['is_correct']) && $answerData['is_correct'],
                ]);
            }
        }
    }
}
