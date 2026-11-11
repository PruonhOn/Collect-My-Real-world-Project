<?php

namespace App\Services;

use App\Models\ClassSection;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Support\Collection;

class EnrollmentService
{
    public function enrollStudent(Student $student, ClassSection $classSection): Enrollment
    {
        $existingEnrollment = Enrollment::where('student_id', $student->id)
            ->where('class_section_id', $classSection->id)
            ->where('status', 'active')
            ->first();

        if ($existingEnrollment) {
            throw new \InvalidArgumentException(
                "Student {$student->id} is already enrolled in class section {$classSection->id}."
            );
        }

        return Enrollment::create([
            'student_id' => $student->id,
            'class_section_id' => $classSection->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
    }

    public function dropStudent(Enrollment $enrollment): Enrollment
    {
        $enrollment->update([
            'status' => 'dropped',
            'dropped_at' => now(),
        ]);

        return $enrollment->fresh();
    }

    public function getEnrolledStudents(ClassSection $classSection): Collection
    {
        return Enrollment::where('class_section_id', $classSection->id)
            ->where('status', 'active')
            ->with(['student', 'student.user'])
            ->get();
    }
}
