<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\Grade;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    protected function resolveSubmission(AssignmentSubmission $submission)
    {
        if (! $submission->assignment->course
            ->teachers()->where('teacher_id', auth()->user()->teacherProfile->id)->exists()) {
            abort(403, __('messages.unauthorized'));
        }

        return $submission;
    }

    public function index(Request $request)
    {
        $teacher = auth()->user()->teacherProfile;

        $query = AssignmentSubmission::with(['assignment.course', 'student'])
            ->whereHas('assignment.course', function ($q) use ($teacher) {
                $q->whereHas('teachers', function ($t) use ($teacher) {
                    $t->where('teacher_id', $teacher->id);
                });
            });

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', '!=', 'graded');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        $submissions = $query->latest()->paginate(15)->withQueryString();

        return view('teacher.submissions.index', compact('submissions'));
    }

    public function grade(AssignmentSubmission $submission)
    {
        $this->resolveSubmission($submission);

        $submission->load(['assignment.course', 'student']);

        return view('teacher.submissions.grade', compact('submission'));
    }

    public function updateGrade(Request $request, AssignmentSubmission $submission)
    {
        $this->resolveSubmission($submission);

        $data = $request->validate([
            'grade' => ['required', 'numeric', 'min:0'],
            'feedback' => ['nullable', 'string'],
            'status' => ['nullable', 'in:submitted,graded'],
        ]);

        $submission->update([
            'grade' => $data['grade'],
            'feedback' => $data['feedback'] ?? null,
            'status' => 'graded',
            'graded_at' => now(),
        ]);

        Grade::updateOrCreate(
            [
                'student_id' => $submission->student_id,
                'assignment_id' => $submission->assignment_id,
                'type' => 'assignment',
            ],
            [
                'course_id' => $submission->assignment->course_id,
                'score' => $data['grade'],
                'max_score' => $submission->assignment->total_marks,
                'note' => $data['feedback'] ?? null,
            ]
        );

        return redirect()->route('teacher.submissions.index')
            ->with('success', __('messages.updated_successfully'));
    }
}
