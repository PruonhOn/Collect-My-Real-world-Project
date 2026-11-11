<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;

class QuizService
{
    /**
     * Create and grade a quiz attempt.
     *
     * @param  array  $answers  question_id => array of selected answer_ids
     */
    public function submitAttempt(int $quizId, int $studentId, array $answers): QuizAttempt
    {
        $quiz = Quiz::with('questions.answers')->findOrFail($quizId);

        $totalMarks = $quiz->questions->sum('marks');
        $earnedMarks = 0;

        $attempt = QuizAttempt::create([
            'quiz_id' => $quizId,
            'student_id' => $studentId,
            'total_marks' => $totalMarks,
            'started_at' => now()->subMinutes(1),
        ]);

        foreach ($quiz->questions as $question) {
            $selected = $answers[$question->id] ?? [];
            $selected = array_map('intval', (array) $selected);

            $correctIds = $question->answers->where('is_correct', true)->pluck('id')
                ->map(fn ($id) => (int) $id)->sort()->values()->all();

            $selectedSorted = collect($selected)->sort()->values()->all();

            $isCorrect = $correctIds === $selectedSorted && count($selected) > 0;

            if ($isCorrect) {
                $earnedMarks += $question->marks;
            }

            QuizAttemptAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'selected_answer_ids' => $selected,
                'is_correct' => $isCorrect,
            ]);
        }

        $percentage = $totalMarks > 0 ? round(($earnedMarks / $totalMarks) * 100, 2) : 0;

        $attempt->update([
            'score' => $earnedMarks,
            'percentage' => $percentage,
            'passed' => $percentage >= (float) $quiz->passing_score,
            'submitted_at' => now(),
        ]);

        return $attempt;
    }
}
