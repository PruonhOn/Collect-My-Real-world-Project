<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponse;

    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function dailySales(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $report = $this->reportService->dailySalesReport($date);
        return $this->success($report);
    }

    public function monthlySales(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        $report = $this->reportService->monthlySalesReport($year, $month);
        return $this->success($report);
    }

    public function productPerformance(Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate = $request->get('end_date', date('Y-m-t'));
        $report = $this->reportService->productPerformanceReport($startDate, $endDate);
        return $this->success($report);
    }

    public function profitLoss(Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate = $request->get('end_date', date('Y-m-t'));
        $report = $this->reportService->profitReport($startDate, $endDate);
        return $this->success($report);
    }

    public function inventory()
    {
        $report = $this->reportService->inventoryReport();
        return $this->success($report);
    }

    public function customers(Request $request)
    {
        $customerId = $request->customer_id;
        $report = $this->reportService->customerReport($customerId);
        return $this->success($report);
    }
}
