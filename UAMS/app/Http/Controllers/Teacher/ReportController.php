<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ClassAttendanceExport;

class ReportController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    protected function authorizeTeacherOwnsSection(ClassSection $section): void
    {
        $teacher = Auth::user()->teacher;
        abort_unless($teacher && $section->teacher_id === $teacher->id, 403);
    }

    public function classReport(ClassSection $classSection, ReportService $reportService)
    {
        $this->authorizeTeacherOwnsSection($classSection);

        $classSection->load(['course', 'academicTerm']);

        [$report, $overallRate] = $this->buildReport($classSection, $reportService);

        return view('teacher.reports.class-report', compact('classSection', 'report', 'overallRate'));
    }

    public function exportPdf(ClassSection $classSection, ReportService $reportService)
    {
        $this->authorizeTeacherOwnsSection($classSection);

        $classSection->load(['course', 'academicTerm']);

        [$report, $overallRate] = $this->buildReport($classSection, $reportService);

        $pdf = Pdf::loadView('teacher.reports.class-report', [
            'classSection' => $classSection,
            'report' => $report,
            'overallRate' => $overallRate,
        ]);

        $filename = "attendance-report-{$classSection->course->code}-{$classSection->name}.pdf";

        return $pdf->download($filename);
    }

    public function exportExcel(ClassSection $classSection, ReportService $reportService)
    {
        $this->authorizeTeacherOwnsSection($classSection);

        [$report, ] = $this->buildReport($classSection, $reportService);

        $filename = "attendance-report-{$classSection->course->code}-{$classSection->name}.xlsx";

        return Excel::download(
            new ClassAttendanceExport($report['students'], count($report['sessions'])),
            $filename
        );
    }

    protected function buildReport(ClassSection $classSection, ReportService $reportService): array
    {
        $rows = $reportService->classReport($classSection);

        $overall = [
            'present' => array_sum(array_column($rows, 'present')),
            'absent' => array_sum(array_column($rows, 'absent')),
            'late' => array_sum(array_column($rows, 'late')),
            'excused' => array_sum(array_column($rows, 'excused')),
        ];

        $totalSessions = $classSection->attendanceSessions()->count();
        $attended = $overall['present'] + $overall['late'] + $overall['excused'];
        $overallRate = $totalSessions > 0 ? round(($attended / ($totalSessions * max(count($rows), 1))) * 100, 1) : 0;

        $report = [
            'students' => $rows,
            'overall' => $overall,
            'sessions' => $classSection->attendanceSessions()->orderByDesc('session_date')->get(),
        ];

        return [$report, $overallRate];
    }
}
