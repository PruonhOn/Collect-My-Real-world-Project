<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Student;

class ProgressService
{
    /**
     * Mark a lesson as completed for a student and update enrollment progress.
     */
    public function completeLesson(int $studentId, int $lessonId): LessonProgress
    {
        $student = Student::findOrFail($studentId);
        $lesson = Lesson::findOrFail($lessonId);

        $enrollment = Enrollment::where('student_id', $studentId)
            ->where('course_id', $lesson->course_id)
            ->first();

        $progress = LessonProgress::updateOrCreate(
            ['student_id' => $studentId, 'lesson_id' => $lessonId],
            [
                'enrollment_id' => $enrollment?->id,
                'is_completed' => true,
                'completed_at' => now(),
                'last_activity_at' => now(),
            ]
        );

        if ($enrollment) {
            $this->recalculateEnrollment($enrollment);
        }

        return $progress;
    }

    /**
     * Record learning time for a lesson.
     */
    public function recordLearningTime(int $studentId, int $lessonId, int $seconds): void
    {
        $progress = LessonProgress::firstOrCreate(
            ['student_id' => $studentId, 'lesson_id' => $lessonId],
            ['last_activity_at' => now()]
        );

        $progress->increment('learning_time_seconds', $seconds);
        $progress->update(['last_activity_at' => now()]);
    }

    /**
     * Recalculate the completion percentage for an enrollment.
     */
    public function recalculateEnrollment(Enrollment $enrollment): void
    {
        $total = $enrollment->course->publishedLessons()->count();

        if ($total === 0) {
            $percentage = 100;
        } else {
            $completed = LessonProgress::where('student_id', $enrollment->student_id)
                ->whereHas('lesson', fn ($q) => $q->where('course_id', $enrollment->course_id))
                ->where('is_completed', true)
                ->count();

            $percentage = round(($completed / $total) * 100, 2);
        }

        $enrollment->update([
            'completion_percentage' => $percentage,
            'status' => $percentage >= 100 ? 'completed' : 'active',
        ]);
    }

    /**
     * Get course progress overview for a student + course.
     */
    public function courseOverview(int $studentId, int $courseId): array
    {
        $total = Lesson::where('course_id', $courseId)->where('is_published', true)->count();
        $completed = LessonProgress::where('student_id', $studentId)
            ->whereHas('lesson', fn ($q) => $q->where('course_id', $courseId))
            ->where('is_completed', true)
            ->count();

        $percentage = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'remaining' => $total - $completed,
            'percentage' => $percentage,
        ];
    }
}
