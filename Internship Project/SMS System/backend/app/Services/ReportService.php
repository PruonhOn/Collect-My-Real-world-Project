<?php

namespace App\Services;

use App\Jobs\GenerateReportJob;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;

final readonly class ReportService
{
    public function __construct(
        private FileService $files,
    ) {}

    /**
     * Create a report request and dispatch its generation to the queue.
     *
     * @param  array<string, mixed>  $params
     */
    public function create(string $type, array $params, ?int $generatedBy = null): Report
    {
        if (! array_key_exists($type, $this->supports())) {
            throw ValidationException::withMessages([
                'type' => "Report type [{$type}] is not supported.",
            ]);
        }

        $report = Report::create([
            'type' => $type,
            'params' => $params,
            'status' => 'pending',
            'generated_by' => $generatedBy,
        ]);

        GenerateReportJob::dispatch($report);

        return $report;
    }

    /**
     * Generate the report file and mark the record completed.
     */
    public function generate(Report $report): Report
    {
        $report->status = 'processing';
        $report->save();

        $data = $this->buildData($report->type, $report->params ?? []);
        $pdf = Pdf::loadView('reports.generic', [
            'title' => $data['title'],
            'headers' => $data['headers'],
            'rows' => $data['rows'],
        ]);

        $file = $this->files->storeContent(
            $pdf->output(),
            str_replace('_', '-', $report->type).'-'.now()->format('YmdHis').'.pdf',
            'application/pdf',
            'report',
            $report,
            $report->generated_by,
        );

        $report->file_id = $file->getKey();
        $report->status = 'completed';
        $report->save();

        return $report;
    }

    /**
     * Report types that can currently be generated.
     *
     * @return array<string, string>
     */
    public function supports(): array
    {
        return [
            'students' => 'Student report',
            'attendance' => 'Attendance report',
            'payments' => 'Payment report',
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{title: string, headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    private function buildData(string $type, array $params): array
    {
        return match ($type) {
            'students' => $this->studentData($params),
            'attendance' => $this->attendanceData($params),
            'payments' => $this->paymentData($params),
            default => ['title' => $type, 'headers' => [], 'rows' => []],
        };
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{title: string, headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    private function studentData(array $params): array
    {
        $query = Student::query()->with('user');

        if (! empty($params['search'])) {
            $query->where(fn ($q) => $q->where('student_code', 'like', "%{$params['search']}%")
                ->orWhere('khmer_name', 'like', "%{$params['search']}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$params['search']}%")));
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $rows = $query->limit(500)->get()->map(fn (Student $s) => [
            $s->student_code,
            $s->user?->name ?? '',
            $s->khmer_name ?? '',
            $s->gender?->value ?? '',
            $s->contact_phone ?? '',
            $s->status?->value ?? '',
        ])->all();

        return [
            'title' => 'Student Report',
            'headers' => ['Code', 'Name', 'Khmer Name', 'Gender', 'Phone', 'Status'],
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{title: string, headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    private function attendanceData(array $params): array
    {
        $query = Attendance::query()
            ->with(['student.user', 'course'])
            ->orderBy('date')
            ->limit(2000);

        if (! empty($params['course_id'])) {
            $course = Course::find($params['course_id']);
            $query->where('course_id', $course?->getKey());
        }

        if (! empty($params['student_id'])) {
            $query->where('student_id', $params['student_id']);
        }

        if (! empty($params['from'])) {
            $query->where('date', '>=', $params['from']);
        }

        if (! empty($params['to'])) {
            $query->where('date', '<=', $params['to']);
        }

        $rows = $query->get()->map(fn (Attendance $a) => [
            $a->date?->toDateString() ?? '',
            $a->course?->name ?? '',
            $a->student?->user?->name ?? '',
            $a->student?->student_code ?? '',
            $a->status->value,
            $a->remark ?? '',
        ])->all();

        return [
            'title' => 'Attendance Report',
            'headers' => ['Date', 'Course', 'Student', 'Code', 'Status', 'Remark'],
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{title: string, headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    private function paymentData(array $params): array
    {
        $query = Payment::query()
            ->with(['student.user', 'studentFee'])
            ->orderByDesc('paid_on')
            ->limit(2000);

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (! empty($params['from'])) {
            $query->where('paid_on', '>=', $params['from'].' 00:00:00');
        }

        if (! empty($params['to'])) {
            $query->where('paid_on', '<=', $params['to'].' 23:59:59');
        }

        $rows = $query->get()->map(fn (Payment $p) => [
            $p->studentFee?->invoice_no ?? '',
            $p->student?->user?->name ?? '',
            $p->student?->student_code ?? '',
            number_format((float) $p->amount, 2),
            $p->method,
            $p->transaction_id ?? '',
            $p->status->value,
            $p->paid_on?->toDateTimeString() ?? '',
        ])->all();

        return [
            'title' => 'Payment Report',
            'headers' => ['Invoice', 'Student', 'Code', 'Amount', 'Method', 'Transaction', 'Status', 'Date'],
            'rows' => $rows,
        ];
    }
}
