<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $dailyDate = $request->get('daily_date', now()->format('Y-m-d'));
        $monthYear = $request->get('month_year', now()->format('Y-m'));
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

        $dailySales = $this->reportService->dailySalesReport($dailyDate);
        $monthParts = explode('-', $monthYear);
        $monthlySales = $this->reportService->monthlySalesReport($monthParts[0] ?? now()->year, $monthParts[1] ?? now()->month);
        $productPerformance = $this->reportService->productPerformanceReport($startDate, $endDate);
        $inventoryReport = $this->reportService->inventoryReport();
        $customerReport = $this->reportService->customerReport();

        return view('reports.index', compact(
            'dailySales', 'monthlySales', 'productPerformance',
            'inventoryReport', 'customerReport',
            'dailyDate', 'monthYear', 'startDate', 'endDate'
        ));
    }

    public function daily(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $sales = $this->reportService->dailySalesReport($date);

        $totalSales = $sales->count();
        $totalRevenue = $sales->sum('grand_total');
        $totalItems = $sales->sum(fn($s) => $s->details->sum('quantity'));
        $avgSale = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

        return response()->json([
            'total_sales' => $totalSales,
            'total_revenue' => $totalRevenue,
            'total_items' => $totalItems,
            'avg_sale' => $avgSale,
            'sales' => $sales->map(fn($s) => [
                'invoice_no' => $s->invoice_no,
                'time' => $s->created_at ? $s->created_at->format('H:i') : '—',
                'customer' => $s->customer ? $s->customer->name : 'Walk-in',
                'items' => $s->details->sum('quantity'),
                'total' => $s->grand_total,
            ]),
        ]);
    }

    public function monthly(Request $request)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);
        $sales = $this->reportService->monthlySalesReport($year, $month);

        return response()->json([
            'sales' => $sales->map(fn($s) => [
                'date' => $s->date,
                'count' => $s->total_sales,
                'items' => 0,
                'revenue' => $s->total_revenue,
            ]),
        ]);
    }

    public function products(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->endOfMonth()->format('Y-m-d'));
        $data = $this->reportService->productPerformanceReport($from, $to);

        return response()->json([
            'products' => $data->map(fn($p) => [
                'name' => $p->name,
                'sku' => $p->sku,
                'category' => '',
                'quantity_sold' => $p->total_quantity,
                'revenue' => $p->total_revenue,
            ]),
        ]);
    }

    public function inventory()
    {
        $products = $this->reportService->inventoryReport();
        $totalProducts = $products->count();
        $inStock = $products->where('current_stock', '>', 0)->count();
        $lowStock = $products->where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'min_stock')->count();
        $outOfStock = $products->where('current_stock', '<=', 0)->count();

        return response()->json([
            'total_products' => $totalProducts,
            'in_stock' => $inStock,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'products' => $products->map(fn($p) => [
                'name' => $p->name,
                'sku' => $p->sku,
                'stock' => $p->current_stock,
                'min_stock' => $p->min_stock,
            ]),
        ]);
    }

    public function customers(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->endOfMonth()->format('Y-m-d'));
        $data = $this->reportService->customerReport();

        return response()->json([
            'customers' => $data->map(fn($c) => [
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone,
                'total_purchases' => $c->total_transactions,
                'total_spent' => $c->total_spent,
                'points' => $c->points ?? 0,
                'last_purchase_date' => $c->last_purchase_at,
            ]),
        ]);
    }
}
