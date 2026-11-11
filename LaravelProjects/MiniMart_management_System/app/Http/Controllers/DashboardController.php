<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ReportService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        $stats = $this->reportService->getDashboardStats();
        $chartData = $this->reportService->getChartData();

        $topProducts = SaleItem::select(
            'product_id',
            DB::raw('SUM(quantity) as total_qty'),
            DB::raw('SUM(total_price) as total_sales')
        )
            ->with('product')
            ->groupBy('product_id')
            ->orderBy('total_qty', 'desc')
            ->take(5)
            ->get();

        return view('dashboard.index', compact('stats', 'chartData', 'topProducts'));
    }
}
