<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    protected function resolveCourse(Course $course)
    {
        if (! $course->teachers()->where('teacher_id', auth()->user()->teacherProfile->id)->exists()) {
            abort(403, __('messages.unauthorized'));
        }

        return $course;
    }

    public function enrollments(Course $course)
    {
        $this->resolveCourse($course);

        $enrollments = $course->enrollments()
            ->with(['student', 'payment'])
            ->paginate(15);

        return view('teacher.students.enrollments', compact('course', 'enrollments'));
    }

    public function show(Student $student)
    {
        $teacher = auth()->user()->teacherProfile;

        if (! $student->courses()
            ->whereHas('teachers', function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })->exists()) {
            abort(403, __('messages.unauthorized'));
        }

        $student->load([
            'enrollments.course',
            'quizAttempts.quiz',
            'assignmentSubmissions.assignment',
            'grades.course',
        ]);

        return view('teacher.students.show', compact('student'));
    }
}
