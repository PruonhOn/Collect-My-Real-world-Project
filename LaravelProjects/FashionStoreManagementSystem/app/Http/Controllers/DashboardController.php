<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly ReportingService $reporting)
    {
    }

    public function index(Request $request): View
    {
        $stats = $this->reporting->dashboardStats();
        $chart = $this->reporting->revenueChartData(30);
        $topProducts = $this->reporting->topProducts(5);
        $lowStock = $this->reporting->lowStockItems(5, 5);
        $outOfStock = $this->reporting->outOfStockItems(5);

        return view('dashboard.index', compact('stats', 'chart', 'topProducts', 'lowStock', 'outOfStock'));
    }

    public function chartData(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);
        $days = min(max($days, 7), 365);

        return response()->json($this->reporting->revenueChartData($days));
    }
}
