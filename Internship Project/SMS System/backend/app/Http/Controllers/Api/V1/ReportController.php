<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\StudentsExport;
use App\Http\Requests\V1\Report\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\AuditService;
use App\Services\FileService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends BaseApiController
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly FileService $files,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Report::query()->with('file');

        if (! $request->user()->can('reports.view')) {
            $query->where('generated_by', $request->user()->getKey());
        }

        $paginator = $this->paginate($query, $request, ['type'], ['status'], 'created_at', 'desc');

        return response()->json([
            'data' => ReportResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function supported(Request $request): JsonResponse
    {
        return response()->json([
            'data' => collect($this->reports->supports())
                ->map(fn (string $label, string $type) => ['type' => $type, 'label' => $label])
                ->values(),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = $this->reports->create(
            $request->string('type')->toString(),
            $request->input('params', []),
            $request->user()->getKey(),
        );

        $this->audit->record('report.requested', $report, after: $report->toArray());

        return response()->json([
            'data' => new ReportResource($report),
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function show(Request $request, Report $report): JsonResponse
    {
        if (! $request->user()->can('reports.view') && $report->generated_by !== $request->user()->getKey()) {
            abort(403);
        }

        return response()->json([
            'data' => new ReportResource($report->load('file')),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function download(Request $request, Report $report): BinaryFileResponse
    {
        if (! $request->user()->can('reports.view') && $report->generated_by !== $request->user()->getKey()) {
            abort(403);
        }

        $file = $report->file;

        if ($file === null) {
            abort(404, 'Report file is not ready yet.');
        }

        return $this->files->download($file, 'report-'.$report->id.'.pdf');
    }

    public function exportStudents(Request $request): BinaryFileResponse
    {
        $query = $request->validate([
            'search' => ['nullable', 'string', 'max:191'],
            'status' => ['nullable', 'string'],
        ]);

        $export = new StudentsExport($query['search'] ?? null, $query['status'] ?? null);

        return $export->download('students.xlsx', Excel::XLSX);
    }
}
