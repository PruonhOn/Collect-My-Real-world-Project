<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Expense;
use App\Models\Sale;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $period = $request->get('period', 'daily');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $report = match ($period) {
            'daily' => $this->reportService->getDailyReport(),
            'weekly' => $this->reportService->getWeeklyReport(),
            'monthly' => $this->reportService->getMonthlyReport(),
            'custom' => $this->reportService->getCustomReport($startDate, $endDate),
            default => $this->reportService->getDailyReport(),
        };

        $recentSales = Sale::with('items.product')
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->latest()
            ->take(10)
            ->get();

        $expenses = Expense::query()
            ->when($startDate, fn($q) => $q->whereDate('date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('date', '<=', $endDate))
            ->latest()
            ->take(10)
            ->get();

        $chartData = $this->reportService->getChartData();

        return view('reports.index', compact(
            'report', 'period', 'startDate', 'endDate',
            'recentSales', 'expenses', 'chartData'
        ));
    }

    public function exportPdf(Request $request)
    {
        $period = $request->get('period', 'daily');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $report = match ($period) {
            'daily' => $this->reportService->getDailyReport(),
            'weekly' => $this->reportService->getWeeklyReport(),
            'monthly' => $this->reportService->getMonthlyReport(),
            'custom' => $this->reportService->getCustomReport($startDate, $endDate),
            default => $this->reportService->getDailyReport(),
        };

        $sales = Sale::with('items.product')
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->latest()
            ->get();

        $expenses = Expense::query()
            ->when($startDate, fn($q) => $q->whereDate('date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('date', '<=', $endDate))
            ->latest()
            ->get();

        $pdf = Pdf::loadView('reports.pdf', compact('report', 'period', 'sales', 'expenses', 'startDate', 'endDate'));
        return $pdf->download('report-' . $period . '-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $period = $request->get('period', 'daily');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $report = match ($period) {
            'daily' => $this->reportService->getDailyReport(),
            'weekly' => $this->reportService->getWeeklyReport(),
            'monthly' => $this->reportService->getMonthlyReport(),
            'custom' => $this->reportService->getCustomReport($startDate, $endDate),
            default => $this->reportService->getDailyReport(),
        };

        return Excel::download(
            new ReportExport($report, $period),
            'report-' . $period . '-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
