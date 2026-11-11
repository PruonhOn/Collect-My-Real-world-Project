<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function store(Request $request, Assignment $assignment)
    {
        $student = auth()->user()->studentProfile;

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $assignment->course_id)
            ->first();

        abort_unless($enrollment, 403, __('messages.unauthorized'));

        $data = $request->validate([
            'comments' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $submission = $assignment->submissions()->firstOrNew([
            'student_id' => $student->id,
        ]);

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('submissions', 'public');
        }

        $submission->comments = $data['comments'] ?? null;
        $submission->status = 'submitted';
        $submission->submitted_at = now();
        $submission->save();

        return back()->with('success', __('messages.submitted_successfully'));
    }
}