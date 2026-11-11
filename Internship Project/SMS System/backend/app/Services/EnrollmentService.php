<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Student;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class EnrollmentService
{
    /**
     * Enroll a student in a course. Capacity is enforced inside a transaction
     * so concurrent enrollment requests cannot oversubscribe a course.
     */
    public function enroll(Student $student, Course $course): CourseEnrollment
    {
        if ($course->trashed()) {
            throw new ModelNotFoundException('Course not found.');
        }

        if ($course->status->value === 'archived') {
            throw new RuntimeException('Cannot enroll into an archived course.');
        }

        return DB::transaction(function () use ($student, $course) {
            $locked = Course::query()
                ->whereKey($course->getKey())
                ->lockForUpdate()
                ->first();

            $enrolled = $locked->enrollments()->count();

            if ($locked->capacity > 0 && $enrolled >= $locked->capacity) {
                throw new RuntimeException('Course capacity has been reached.');
            }

            return CourseEnrollment::firstOrCreate([
                'course_id' => $course->getKey(),
                'student_id' => $student->getKey(),
            ], [
                'enrolled_on' => now()->toDateString(),
            ]);
        });
    }

    /**
     * Remove a student from a course (soft delete keeps the record).
     */
    public function remove(CourseEnrollment $enrollment): void
    {
        $enrollment->delete();
    }
}
