<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        $stats = $this->reportService->dashboardStats();
        return $this->success($stats);
    }

    public function salesChart(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $data = [];

        switch ($period) {
            case 'daily':
                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i)->format('Y-m-d');
                    $sales = Sale::whereDate('sale_date', $date)->where('status', 'completed')->sum('grand_total');
                    $data['labels'][] = now()->subDays($i)->format('D');
                    $data['values'][] = (float) $sales;
                }
                break;
            case 'monthly':
                for ($i = 11; $i >= 0; $i--) {
                    $date = now()->subMonths($i);
                    $sales = Sale::whereYear('sale_date', $date->year)->whereMonth('sale_date', $date->month)->where('status', 'completed')->sum('grand_total');
                    $data['labels'][] = $date->format('M');
                    $data['values'][] = (float) $sales;
                }
                break;
            case 'yearly':
                for ($i = 4; $i >= 0; $i--) {
                    $year = now()->subYears($i)->year;
                    $sales = Sale::whereYear('sale_date', $year)->where('status', 'completed')->sum('grand_total');
                    $data['labels'][] = (string) $year;
                    $data['values'][] = (float) $sales;
                }
                break;
        }

        return $this->success($data);
    }
}
