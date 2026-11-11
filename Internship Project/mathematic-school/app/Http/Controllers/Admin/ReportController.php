<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reportType = $request->get('report', 'students');
        $data = [];
        $stats = [];

        $query = match ($reportType) {
            'students' => Student::query(),
            'enrollments' => Enrollment::with(['student', 'course']),
            'payments' => Payment::with(['student', 'course']),
            'courses' => Course::withCount('enrollments'),
            default => Student::query(),
        };

        if ($request->filled('from_date')) {
            $dateCol = match ($reportType) {
                'enrollments' => 'enrolled_at',
                'payments' => 'created_at',
                default => 'created_at',
            };
            $query->where($dateCol, '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $dateCol = match ($reportType) {
                'enrollments' => 'enrolled_at',
                'payments' => 'created_at',
                default => 'created_at',
            };
            $query->where($dateCol, '<=', $request->to_date);
        }

        $data = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'students' => Student::count(),
            'courses' => Course::count(),
            'enrollments' => Enrollment::count(),
            'revenue' => Payment::where('status', 'paid')->sum('amount'),
            'teachers' => Teacher::count(),
        ];

        return view('admin.reports.index', compact('reportType', 'data', 'stats'));
    }

    public function export(Request $request): StreamedResponse
    {
        $reportType = $request->get('report', 'students');
        $filename = "report_{$reportType}_" . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($reportType, $request) {
            $handle = fopen('php://output', 'w');

            match ($reportType) {
                'students' => $this->exportStudents($handle, $request),
                'enrollments' => $this->exportEnrollments($handle, $request),
                'payments' => $this->exportPayments($handle, $request),
                'courses' => $this->exportCourses($handle, $request),
                default => null,
            };

            fclose($handle);
        }, 200, $headers);
    }

    private function exportStudents($handle, Request $request): void
    {
        fputcsv($handle, ['ID', 'ឈ្មោះ', 'ភេទ', 'អាយុ', 'ទូរស័ព្ទ', 'អាសយដ្ឋាន', 'ស្ថានភាព']);

        $query = Student::query();
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $query->latest()->chunk(200, function ($students) use ($handle) {
            foreach ($students as $s) {
                fputcsv($handle, [
                    $s->student_id,
                    $s->full_name,
                    $s->gender,
                    $s->age,
                    $s->phone,
                    $s->address,
                    $s->status,
                ]);
            }
        });
    }

    private function exportEnrollments($handle, Request $request): void
    {
        fputcsv($handle, ['ID', 'សិស្ស', 'វគ្គសិក្សា', 'ស្ថានភាព', 'ភាគរយបញ្ចប់', 'កាលបរិច្ឆេទចុះឈ្មោះ']);

        $query = Enrollment::with(['student', 'course']);
        if ($request->filled('from_date')) {
            $query->where('enrolled_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('enrolled_at', '<=', $request->to_date);
        }

        $query->latest()->chunk(200, function ($enrollments) use ($handle) {
            foreach ($enrollments as $e) {
                fputcsv($handle, [
                    $e->id,
                    $e->student->full_name ?? '',
                    $e->course->title ?? '',
                    $e->status,
                    $e->completion_percentage . '%',
                    $e->enrolled_at?->format('Y-m-d'),
                ]);
            }
        });
    }

    private function exportPayments($handle, Request $request): void
    {
        fputcsv($handle, ['ID', 'សិស្ស', 'វគ្គសិក្សា', 'ចំនួនទឹកប្រាក់', 'វិធីសាស្ត្រ', 'ស្ថានភាព', 'កាលបរិច្ឆេទបង់ប្រាក់']);

        $query = Payment::with(['student', 'course']);
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $query->latest()->chunk(200, function ($payments) use ($handle) {
            foreach ($payments as $p) {
                fputcsv($handle, [
                    $p->id,
                    $p->student->full_name ?? '',
                    $p->course->title ?? '',
                    $p->amount,
                    $p->method,
                    $p->status,
                    $p->paid_date?->format('Y-m-d'),
                ]);
            }
        });
    }

    private function exportCourses($handle, Request $request): void
    {
        fputcsv($handle, ['ID', 'លេខកូដ', 'ចំណងជើង', 'ប្រភេទ', 'ក្រុមអាយុ', 'តម្លៃ', 'ស្ថានភាព', 'ចំនួនសិស្ស']);

        $query = Course::withCount('enrollments');
        $query->latest()->chunk(200, function ($courses) use ($handle) {
            foreach ($courses as $c) {
                fputcsv($handle, [
                    $c->id,
                    $c->course_code,
                    $c->title,
                    $c->category->name ?? '',
                    $c->age_group,
                    $c->price,
                    $c->status,
                    $c->enrollments_count,
                ]);
            }
        });
    }
}
