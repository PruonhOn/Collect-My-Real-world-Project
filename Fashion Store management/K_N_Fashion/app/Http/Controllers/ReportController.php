<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function salesReport(Request $request)
    {
        $query = Sale::with('customer', 'user');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sales = $query->latest()->paginate(15);

        $totalSales = (clone $query)->sum('grand_total');
        $totalDiscount = (clone $query)->sum('discount');
        $totalTax = (clone $query)->sum('tax');
        $salesCount = (clone $query)->count();

        return view('reports.sales', compact(
            'sales', 'totalSales', 'totalDiscount', 'totalTax', 'salesCount'
        ));
    }

    public function inventoryReport()
    {
        $products = Product::with('variants', 'category')->get();

        $lowStockThreshold = 10;
        $lowStockProducts = $products->filter(function ($product) use ($lowStockThreshold) {
            return $product->total_stock < $lowStockThreshold;
        });

        $totalStockValue = $products->sum(function ($product) {
            return $product->total_stock * $product->cost_price;
        });

        $totalStockValueSelling = $products->sum(function ($product) {
            return $product->total_stock * $product->selling_price;
        });

        return view('reports.inventory', compact(
            'products', 'lowStockProducts', 'totalStockValue', 'totalStockValueSelling', 'lowStockThreshold'
        ));
    }

    public function profitReport(Request $request)
    {
        $dateFrom = $request->date_from ?: now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?: now()->format('Y-m-d');

        $totalSales = Sale::whereBetween('created_at', [$dateFrom, $dateTo])
            ->sum('grand_total');

        $totalCost = SaleItem::whereHas('sale', function ($q) use ($dateFrom, $dateTo) {
            $q->whereBetween('created_at', [$dateFrom, $dateTo]);
        })->join('products', 'sale_items.product_id', '=', 'products.id')
            ->select(DB::raw('SUM(sale_items.quantity * products.cost_price) as total_cost'))
            ->value('total_cost') ?? 0;

        $totalExpenses = Expense::whereBetween('date', [$dateFrom, $dateTo])
            ->sum('amount');

        $grossProfit = $totalSales - $totalCost;
        $netProfit = $grossProfit - $totalExpenses;

        $salesCount = Sale::whereBetween('created_at', [$dateFrom, $dateTo])->count();

        $monthlyData = Sale::whereBetween('created_at', [$dateFrom, $dateTo])
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"), DB::raw('SUM(grand_total) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('reports.profit', compact(
            'totalSales', 'totalCost', 'totalExpenses', 'grossProfit', 'netProfit',
            'salesCount', 'dateFrom', 'dateTo', 'monthlyData'
        ));
    }
}
