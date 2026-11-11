<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Enrollment;
use App\Models\Grade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->studentProfile;

        if (! $student) {
            return response()->json(['message' => 'រកមិនឃើញសិស្ស។'], 404);
        }

        $courseIds = $student->enrollments()->pluck('course_id');

        $assignments = Assignment::where('is_published', true)
            ->whereHas('course', fn ($q) => $q->where('status', 'published'))
            ->whereIn('course_id', $courseIds)
            ->with(['course', 'submissions' => fn ($q) => $q->where('student_id', $student->id)])
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($assignments);
    }

    public function show(Assignment $assignment): JsonResponse
    {
        $student = request()->user()->studentProfile;

        if (! $student) {
            return response()->json(['message' => 'រកមិនឃើញសិស្ស។'], 404);
        }

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $assignment->course_id)
            ->first();

        if (! $enrollment) {
            return response()->json(['message' => 'អ្នកមិនមានសិទ្ធិចូលប្រើទំព័រនេះទេ។'], 403);
        }

        $assignment->load('course');

        $submission = $assignment->submissions()
            ->where('student_id', $student->id)
            ->first();

        return response()->json([
            'assignment' => $assignment,
            'submission' => $submission,
        ]);
    }

    public function submit(Request $request, Assignment $assignment): JsonResponse
    {
        $student = $request->user()->studentProfile;

        if (! $student) {
            return response()->json(['message' => 'រកមិនឃើញសិស្ស។'], 404);
        }

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $assignment->course_id)
            ->first();

        if (! $enrollment) {
            return response()->json(['message' => 'អ្នកមិនមានសិទ្ធិចូលប្រើទំព័រនេះទេ។'], 403);
        }

        $request->validate([
            'comments' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $submission = $assignment->submissions()->firstOrNew([
            'student_id' => $student->id,
        ]);

        $data = [];

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('submissions', 'public');
        }

        $data['comments'] = $request->comments ?? null;
        $data['status'] = 'submitted';
        $data['submitted_at'] = now();

        $submission->fill($data);
        $submission->save();

        return response()->json([
            'message' => 'បានដាក់ស្នើដោយជោគជ័យ។',
            'submission' => $submission,
        ]);
    }
}
