<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->format('Y-m-d');
        $thisMonth = now()->format('Y-m');
        $thisYear = now()->year;

        $data = [
            'todaySales' => Sale::whereDate('sale_date', $today)->where('status', 'completed')->sum('grand_total'),
            'monthlySales' => Sale::whereYear('sale_date', now()->year)->whereMonth('sale_date', now()->month)->where('status', 'completed')->sum('grand_total'),
            'totalProducts' => Product::where('status', 'active')->count(),
            'lowStockCount' => Product::where('status', 'active')->whereColumn('current_stock', '<=', 'min_stock')->count(),
            'totalCustomers' => Customer::count(),
            'monthlyExpenses' => Expense::whereYear('expense_date', now()->year)->whereMonth('expense_date', now()->month)->where('status', 'paid')->sum('amount'),

            'recentSales' => Sale::with('customer:id,name')
                ->where('status', 'completed')
                ->latest()
                ->take(10)
                ->get(),

            'topProducts' => DB::table('sale_details')
                ->join('products', 'sale_details.product_id', '=', 'products.id')
                ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                ->select('products.id', 'products.name', 'products.sku', DB::raw('SUM(sale_details.quantity) as total_qty'), DB::raw('SUM(sale_details.subtotal) as total_revenue'))
                ->whereYear('sales.sale_date', $thisYear)
                ->where('sales.status', 'completed')
                ->groupBy('products.id', 'products.name', 'products.sku')
                ->orderByDesc('total_qty')
                ->take(5)
                ->get(),

            'chartLabels' => [],
            'chartValues' => [],
        ];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $sales = Sale::whereDate('sale_date', $date)->where('status', 'completed')->sum('grand_total');
            $data['chartLabels'][] = now()->subDays($i)->format('D');
            $data['chartValues'][] = (float) $sales;
        }

        return view('dashboard.index', $data);
    }
}
