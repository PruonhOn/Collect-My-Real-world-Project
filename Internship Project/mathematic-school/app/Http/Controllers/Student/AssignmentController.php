<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index()
    {
        $student = auth()->user()->studentProfile;

        $courseIds = $student->enrollments()->pluck('course_id');

        $assignments = Assignment::where('is_published', true)
            ->whereHas('course', fn ($q) => $q->where('status', 'published'))
            ->whereIn('course_id', $courseIds)
            ->with(['course', 'submissions' => fn ($q) => $q->where('student_id', $student->id)])
            ->latest()
            ->paginate(15);

        return view('student.assignments.index', compact('assignments'));
    }

    public function show(Assignment $assignment)
    {
        $student = auth()->user()->studentProfile;

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $assignment->course_id)
            ->first();

        abort_unless($enrollment, 403, __('messages.unauthorized'));

        $assignment->load('course');

        $submission = $assignment->submissions()
            ->where('student_id', $student->id)
            ->first();

        return view('student.assignments.show', compact('assignment', 'submission'));
    }
}