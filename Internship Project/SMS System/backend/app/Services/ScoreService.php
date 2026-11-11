<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Score;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class ScoreService
{
    /**
     * Submit or update a score for one student on an assessment.
     *
     * @param  array{score: float|int|string, remark?: string|null}  $data
     */
    public function submit(Assessment $assessment, Student $student, array $data, ?int $createdBy = null): Score
    {
        $this->guardScore($assessment, $data['score']);

        return Score::updateOrCreate(
            [
                'assessment_id' => $assessment->getKey(),
                'student_id' => $student->getKey(),
            ],
            [
                'score' => $data['score'],
                'remark' => $data['remark'] ?? null,
                'created_by' => $createdBy,
            ],
        );
    }

    /**
     * Submit scores for many students on the same assessment.
     *
     * @param  array<int, array{student_id: int, score: float|int|string, remark?: string|null}>  $rows
     */
    public function submitBulk(Assessment $assessment, array $rows, ?int $createdBy = null): Collection
    {
        $enrolledStudentIds = $assessment->course->students()->pluck('students.id')->all();

        return collect($rows)
            ->map(function (array $row) use ($assessment, $enrolledStudentIds, $createdBy) {
                if (! in_array((int) $row['student_id'], $enrolledStudentIds, true)) {
                    throw ValidationException::withMessages([
                        'rows' => sprintf('Student #%d is not enrolled in this course.', $row['student_id']),
                    ]);
                }

                return $this->submit(
                    $assessment,
                    Student::findOrFail($row['student_id']),
                    $row,
                    $createdBy,
                );
            });
    }

    /**
     * A weighted percentage summary for a student within a course.
     */
    public function weightedPercentage(int $courseId, int $studentId): array
    {
        $scores = Score::query()
            ->where('student_id', $studentId)
            ->whereHas('assessment', fn ($q) => $q->where('course_id', $courseId)->where('weight', '>', 0))
            ->with('assessment')
            ->get();

        $earned = 0.0;
        $totalWeight = 0.0;

        foreach ($scores as $score) {
            $totalWeight += (float) $score->assessment->weight;
            $earned += ((float) $score->score / max((float) $score->assessment->max_score, 1)) * (float) $score->assessment->weight;
        }

        return [
            'weighted_percentage' => $totalWeight > 0 ? round($earned / $totalWeight * 100, 2) : null,
            'assessments_scored' => $scores->count(),
            'total_weight' => $totalWeight,
        ];
    }

    private function guardScore(Assessment $assessment, mixed $score): void
    {
        if (! is_numeric($score) || (float) $score < 0 || (float) $score > (float) $assessment->max_score) {
            throw ValidationException::withMessages([
                'score' => sprintf('Score must be between 0 and %s.', $assessment->max_score),
            ]);
        }
    }
}
