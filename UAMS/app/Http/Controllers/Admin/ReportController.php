<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\ClassSection;
use App\Models\Department;
use App\Models\Student;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ClassReportExport;
use App\Exports\StudentReportExport;

class ReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ReportService $reportService
    ) {}

    public function studentReport(Request $request)
    {
        $this->authorize('permission:view-reports');

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'term_id' => 'nullable|exists:academic_terms,id',
        ]);

        $student = Student::with(['user', 'department.faculty'])->findOrFail($request->input('student_id'));
        $term = $request->filled('term_id')
            ? AcademicTerm::findOrFail($request->input('term_id'))
            : null;

        $reportData = $this->reportService->getStudentReport(
            $request->input('student_id'),
            $term?->id
        );

        $terms = AcademicTerm::latest('start_date')->get();

        return view('admin.reports.student', compact('student', 'term', 'reportData', 'terms'));
    }

    public function classReport(Request $request)
    {
        $this->authorize('permission:view-reports');

        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
        ]);

        $classSection = ClassSection::with([
            'course.program.department',
            'teacher.user',
            'academicTerm',
        ])->findOrFail($request->input('class_section_id'));

        $reportData = $this->reportService->getClassReport($request->input('class_section_id'));

        return view('admin.reports.class', compact('classSection', 'reportData'));
    }

    public function departmentReport(Request $request)
    {
        $this->authorize('permission:view-reports');

        $request->validate([
            'department_id' => 'required|exists:departments,id',
        ]);

        $department = Department::with('faculty')->findOrFail($request->input('department_id'));
        $term = $request->filled('term_id')
            ? AcademicTerm::findOrFail($request->input('term_id'))
            : AcademicTerm::where('is_active', true)->first();

        $reportData = $this->reportService->getDepartmentReport(
            $request->input('department_id'),
            $term?->id
        );

        $terms = AcademicTerm::latest('start_date')->get();

        return view('admin.reports.department', compact('department', 'term', 'reportData', 'terms'));
    }

    public function termReport(Request $request)
    {
        $this->authorize('permission:view-reports');

        $request->validate([
            'term_id' => 'required|exists:academic_terms,id',
        ]);

        $term = AcademicTerm::findOrFail($request->input('term_id'));

        $reportData = $this->reportService->getTermReport($request->input('term_id'));

        return view('admin.reports.term', compact('term', 'reportData'));
    }

    public function exportStudentReportPdf(Request $request)
    {
        $this->authorize('permission:view-reports');

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'term_id' => 'nullable|exists:academic_terms,id',
        ]);

        $student = Student::with(['user', 'department.faculty'])->findOrFail($request->input('student_id'));
        $term = $request->filled('term_id')
            ? AcademicTerm::findOrFail($request->input('term_id'))
            : null;

        $reportData = $this->reportService->getStudentReport(
            $request->input('student_id'),
            $term?->id
        );

        $pdf = Pdf::loadView('admin.reports.partials.student-pdf', compact('student', 'term', 'reportData'));

        $filename = "student_report_{$student->student_code}" . ($term ? "_{$term->name}" : '') . '.pdf';

        return $pdf->download($filename);
    }

    public function exportClassReportExcel(Request $request)
    {
        $this->authorize('permission:view-reports');

        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
        ]);

        $classSection = ClassSection::with([
            'course',
            'teacher.user',
            'academicTerm',
        ])->findOrFail($request->input('class_section_id'));

        $filename = "class_report_{$classSection->course->code}_{$classSection->section_name}.xlsx";

        return Excel::download(
            new ClassReportExport($request->input('class_section_id')),
            $filename
        );
    }
}
