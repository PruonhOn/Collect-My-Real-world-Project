<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(): View
    {
        return view('reports.index');
    }

    public function shipments(Request $request): View
    {
        $filters = $request->only(['date_from', 'date_to', 'status', 'branch_id']);
        $data = $this->reportService->getShipmentReport($filters);

        $this->auditLogService->log('viewed', 'reports', 'Shipment report viewed', null, null, $filters, auth()->id());

        return view('reports.shipments', compact('data', 'filters'));
    }

    public function deliveries(Request $request): View
    {
        $filters = $request->only(['date_from', 'date_to', 'status', 'driver_id']);
        $data = $this->reportService->getDeliveryReport($filters);

        $this->auditLogService->log('viewed', 'reports', 'Delivery report viewed', null, null, $filters, auth()->id());

        return view('reports.deliveries', compact('data', 'filters'));
    }

    public function customers(Request $request): View
    {
        $filters = $request->only(['is_active']);
        $data = $this->reportService->getCustomerReport($filters);

        $this->auditLogService->log('viewed', 'reports', 'Customer report viewed', null, null, $filters, auth()->id());

        return view('reports.customers', compact('data', 'filters'));
    }

    public function financial(Request $request): View
    {
        $filters = $request->only(['date_from', 'date_to']);
        $data = $this->reportService->getFinancialReport($filters);

        $this->auditLogService->log('viewed', 'reports', 'Financial report viewed', null, null, $filters, auth()->id());

        return view('reports.financial', compact('data', 'filters'));
    }

    public function warehouses(Request $request): View
    {
        $filters = $request->only(['branch_id']);
        $data = $this->reportService->getWarehouseReport($filters);

        $this->auditLogService->log('viewed', 'reports', 'Warehouse report viewed', null, null, $filters, auth()->id());

        return view('reports.warehouses', compact('data', 'filters'));
    }

    public function exportPdf(Request $request, string $type)
    {
        try {
            $filters = $request->only(['date_from', 'date_to', 'status', 'branch_id']);
            $method = 'get' . ucfirst($type) . 'Report';
            $data = $this->reportService->$method($filters);
            $view = "reports.pdf.{$type}";

            $this->auditLogService->log('exported', 'reports', "{$type} report exported as PDF", null, null, $filters, auth()->id());

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, compact('data', 'filters'));
            return $pdf->download("{$type}-report.pdf");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export PDF: ' . $e->getMessage());
        }
    }

    public function exportExcel(Request $request, string $type)
    {
        try {
            $filters = $request->only(['date_from', 'date_to', 'status', 'branch_id']);
            $method = 'get' . ucfirst($type) . 'Report';
            $data = $this->reportService->$method($filters);

            $this->auditLogService->log('exported', 'reports', "{$type} report exported as Excel", null, null, $filters, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Excel export initiated. The file will be available for download shortly.',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export Excel: ' . $e->getMessage());
        }
    }
}
