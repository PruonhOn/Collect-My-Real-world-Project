<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Grade;
use App\Models\Score;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class GradeService
{
    /**
     * Create an assessment for a course and ensure total weights stay <= 100.
     *
     * @param  array{title: string, type: string, max_score: float|int|string, weight: float|int|string, date?: string|null}  $data
     */
    public function createAssessment(Course $course, array $data): Assessment
    {
        $assessment = Assessment::create([
            'course_id' => $course->getKey(),
            'title' => $data['title'],
            'type' => $data['type'],
            'max_score' => $data['max_score'],
            'weight' => $data['weight'],
            'date' => $data['date'] ?? null,
        ]);

        $this->assertWeightsWithin($course);

        return $assessment;
    }

    /**
     * Reject the change when weighted assessments would sum to more than 100%.
     */
    public function assertWeightsWithin(Course $course): void
    {
        $total = Assessment::query()
            ->where('course_id', $course->getKey())
            ->sum('weight');

        if ((float) $total > 100.0) {
            throw ValidationException::withMessages([
                'weight' => sprintf('Combined assessment weights for this course exceed 100%% (currently %.2f%%).', $total),
            ]);
        }
    }

    /**
     * Recalculate a final grade for an enrollment from weighted assessment
     * scores, and optionally assign credit hours defaulting to the course's
     * subject-based credit (1.0).
     */
    public function computeAndStore(CourseEnrollment $enrollment, float $creditHours = 1.0): Grade
    {
        $summary = $this->weightedPercentageForEnrollment($enrollment);

        return Grade::updateOrCreate(
            ['enrollment_id' => $enrollment->getKey()],
            [
                'letter_grade' => $summary['weighted_percentage'] !== null ? $this->letter($summary['weighted_percentage']) : null,
                'points' => $summary['weighted_percentage'] !== null ? $this->qualityPoints($summary['weighted_percentage']) : 0,
                'credit_hours' => $creditHours,
                'remarks' => $summary['weighted_percentage'] !== null ? null : 'Not enough graded assessments.',
            ],
        );
    }

    public function weightedPercentageForEnrollment(CourseEnrollment $enrollment): array
    {
        $scores = Score::query()
            ->where('student_id', $enrollment->student_id)
            ->whereHas('assessment', fn ($q) => $q->where('course_id', $enrollment->course_id)->where('weight', '>', 0))
            ->with('assessment')
            ->get();

        return $this->aggregate($scores);
    }

    /**
     * Aggregate a list of scores into a weighted percentage.
     *
     * @param  Collection<int, Score>  $scores
     */
    public function aggregate(Collection $scores): array
    {
        $earned = 0.0;
        $totalWeight = 0.0;

        foreach ($scores as $score) {
            $totalWeight += (float) $score->assessment->weight;
            $earned += ((float) $score->score / max((float) $score->assessment->max_score, 1)) * (float) $score->assessment->weight;
        }

        $percentage = $totalWeight > 0 ? round($earned / $totalWeight * 100, 2) : null;

        return [
            'weighted_percentage' => $percentage,
            'assessments_scored' => $scores->count(),
            'total_weight' => $totalWeight,
            'letter_grade' => $percentage !== null ? $this->letter($percentage) : null,
            'points' => $percentage !== null ? $this->qualityPoints($percentage) : 0,
        ];
    }

    public function letter(float $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'A',
            $percentage >= 80 => 'B',
            $percentage >= 70 => 'C',
            $percentage >= 60 => 'D',
            default => 'F',
        };
    }

    public function qualityPoints(float $percentage): float
    {
        return match (true) {
            $percentage >= 90 => 4.0,
            $percentage >= 80 => 3.0,
            $percentage >= 70 => 2.0,
            $percentage >= 60 => 1.0,
            default => 0.0,
        };
    }
}
