<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Student;

class CertificateController extends Controller
{
    public function index()
    {
        $student = auth()->user()->studentProfile;

        $certificates = $student->certificates()
            ->with('course')
            ->latest()
            ->paginate(15);

        return view('student.certificates.index', compact('certificates'));
    }

    public function show(Certificate $certificate)
    {
        $student = auth()->user()->studentProfile;

        abort_unless($certificate->student_id === $student->id, 403, __('messages.unauthorized'));

        $certificate->load(['student', 'course', 'course.teachers']);

        return view('student.certificates.show', compact('certificate'));
    }

    public function download(Certificate $certificate)
    {
        $student = auth()->user()->studentProfile;
        abort_unless($certificate->student_id === $student->id, 403, __('messages.unauthorized'));

        return redirect()->route('student.certificates.show', $certificate);
    }
}