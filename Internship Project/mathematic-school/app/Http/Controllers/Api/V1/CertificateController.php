<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->studentProfile;

        if (! $student) {
            return response()->json(['message' => 'រកមិនឃើញសិស្ស។'], 404);
        }

        $certificates = $student->certificates()
            ->with('course')
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($certificates);
    }

    public function show(Certificate $certificate): JsonResponse
    {
        $student = request()->user()->studentProfile;

        if (! $student || $certificate->student_id !== $student->id) {
            return response()->json(['message' => 'អ្នកមិនមានសិទ្ធិចូលប្រើទំព័រនេះទេ។'], 403);
        }

        $certificate->load(['student', 'course', 'course.teachers']);

        return response()->json($certificate);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'certificate_id' => ['required', 'string'],
        ]);

        $certificate = Certificate::with(['student', 'course'])
            ->where('certificate_id', $request->certificate_id)
            ->first();

        if (! $certificate) {
            return response()->json([
                'valid' => false,
                'message' => 'វិញ្ញាបនបត្រមិនត្រឹមត្រូវ។',
            ]);
        }

        return response()->json([
            'valid' => true,
            'certificate' => [
                'certificate_id' => $certificate->certificate_id,
                'student_name' => $certificate->student->full_name,
                'course_title' => $certificate->course->title,
                'issued_date' => $certificate->issued_date?->format('Y-m-d'),
            ],
        ]);
    }
}
