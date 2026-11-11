<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $query = Certificate::with(['student', 'course']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('certificate_id', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($sq) => $sq->where('full_name', 'like', "%{$search}%"));
            });
        }

        $certificates = $query->latest()->paginate(15)->withQueryString();

        return view('admin.certificates.index', compact('certificates'));
    }

    public function create()
    {
        $completedEnrollments = Enrollment::with(['student', 'course'])
            ->where('completion_percentage', '>=', 100)
            ->whereDoesntHave('certificate')
            ->get();

        return view('admin.certificates.create', compact('completedEnrollments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'enrollment_id' => ['required', 'exists:enrollments,id'],
        ]);

        $enrollment = Enrollment::with('student', 'course')->findOrFail($data['enrollment_id']);

        $existing = Certificate::where('student_id', $enrollment->student_id)
            ->where('course_id', $enrollment->course_id)
            ->exists();

        if ($existing) {
            return back()->withErrors(['enrollment_id' => 'វិញ្ញាបនបត្រសម្រាប់សិស្សនេះក្នុងវគ្គសិក្សានេះមានរួចហើយ។'])
                ->withInput();
        }

        Certificate::create([
            'student_id' => $enrollment->student_id,
            'course_id' => $enrollment->course_id,
            'certificate_id' => 'CERT-' . strtoupper(Str::random(10)),
            'issued_date' => now(),
        ]);

        return redirect()->route('admin.certificates.index')
            ->with('success', __('messages.created_successfully'));
    }

    public function show(Certificate $certificate)
    {
        $certificate->load(['student', 'course']);

        return view('admin.certificates.show', compact('certificate'));
    }

    public function destroy(Certificate $certificate)
    {
        $certificate->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }
}
