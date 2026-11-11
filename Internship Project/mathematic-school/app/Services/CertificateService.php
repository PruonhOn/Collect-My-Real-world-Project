<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Support\Str;

class CertificateService
{
    /**
     * Issue a certificate for a student who completed a course.
     */
    public function issueCertificate(int $studentId, int $courseId): Certificate
    {
        $certificate = Certificate::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->first();

        if ($certificate) {
            return $certificate;
        }

        $certificate = Certificate::create([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'certificate_id' => $this->generateId(),
            'issued_date' => now(),
        ]);

        return $certificate;
    }

    /**
     * Issue a certificate if the student reached 100% completion.
     */
    public function ensureCompletionCertificate(Enrollment $enrollment): ?Certificate
    {
        if ((float) $enrollment->completion_percentage >= 100) {
            return $this->issueCertificate($enrollment->student_id, $enrollment->course_id);
        }

        return null;
    }

    public function generateId(): string
    {
        return 'CERT-' . now()->format('Y') . '-' . strtoupper(Str::random(8));
    }
}
