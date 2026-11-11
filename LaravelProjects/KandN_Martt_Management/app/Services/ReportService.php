<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function dailySalesReport(string $date = null)
    {
        $date = $date ?? date('Y-m-d');

        return Sale::with(['details.product', 'customer', 'creator'])
            ->whereDate('sale_date', $date)
            ->where('status', 'completed')
            ->get();
    }

    public function monthlySalesReport(int $year = null, int $month = null)
    {
        $year = $year ?? date('Y');
        $month = $month ?? date('m');

        return Sale::select(
            DB::raw('DATE(sale_date) as date'),
            DB::raw('COUNT(*) as total_sales'),
            DB::raw('SUM(grand_total) as total_revenue'),
            DB::raw('SUM(discount_amount) as total_discount'),
            DB::raw('SUM(tax_amount) as total_tax')
        )
            ->whereYear('sale_date', $year)
            ->whereMonth('sale_date', $month)
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function productPerformanceReport(string $startDate = null, string $endDate = null)
    {
        $startDate = $startDate ?? date('Y-m-01');
        $endDate = $endDate ?? date('Y-m-t');

        return DB::table('sale_details')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(sale_details.quantity) as total_quantity'),
                DB::raw('SUM(sale_details.subtotal) as total_revenue'),
                DB::raw('AVG(sale_details.unit_price) as avg_price'),
                DB::raw('COUNT(DISTINCT sales.id) as sale_count')
            )
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->where('sales.status', 'completed')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_quantity')
            ->get();
    }

    public function profitReport(string $startDate = null, string $endDate = null)
    {
        $startDate = $startDate ?? date('Y-m-01');
        $endDate = $endDate ?? date('Y-m-t');

        $totalSales = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('grand_total');

        $totalPurchases = Purchase::whereBetween('purchase_date', [$startDate, $endDate])
            ->where('status', 'received')
            ->sum('grand_total');

        $totalExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('amount');

        $grossProfit = $totalSales - $totalPurchases;
        $netProfit = $grossProfit - $totalExpenses;

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_sales' => $totalSales,
            'total_purchases' => $totalPurchases,
            'total_expenses' => $totalExpenses,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'profit_margin' => $totalSales > 0 ? ($netProfit / $totalSales) * 100 : 0,
        ];
    }

    public function inventoryReport()
    {
        return Product::with(['category:id,name', 'stock'])
            ->where('status', 'active')
            ->get(['id', 'name', 'sku', 'category_id', 'cost_price', 'selling_price', 'current_stock', 'min_stock']);
    }

    public function customerReport(int $customerId = null)
    {
        $query = DB::table('customers')
            ->leftJoin('sales', 'customers.id', '=', 'sales.customer_id')
            ->select(
                'customers.id',
                'customers.name',
                'customers.email',
                'customers.phone',
                'customers.points',
                'customers.total_purchases',
                'customers.last_purchase_at',
                DB::raw('COUNT(sales.id) as total_transactions'),
                DB::raw('COALESCE(SUM(sales.grand_total), 0) as total_spent'),
                DB::raw('AVG(sales.grand_total) as avg_transaction_value')
            )
            ->groupBy('customers.id', 'customers.name', 'customers.email', 'customers.phone', 'customers.points', 'customers.total_purchases', 'customers.last_purchase_at')
            ->orderByDesc('total_spent');

        if ($customerId) {
            $query->where('customers.id', $customerId);
        }

        return $query->get();
    }

    public function dashboardStats()
    {
        $today = date('Y-m-d');
        $thisMonth = date('Y-m');

        return [
            'today_sales' => Sale::whereDate('sale_date', $today)->where('status', 'completed')->sum('grand_total'),
            'monthly_sales' => Sale::whereYear('sale_date', date('Y'))->whereMonth('sale_date', date('m'))->where('status', 'completed')->sum('grand_total'),
            'total_products' => Product::where('status', 'active')->count(),
            'low_stock_products' => Product::where('status', 'active')->whereColumn('current_stock', '<=', 'min_stock')->count(),
            'total_customers' => \App\Models\Customer::count(),
            'monthly_expenses' => Expense::whereYear('expense_date', date('Y'))->whereMonth('expense_date', date('m'))->where('status', 'paid')->sum('amount'),
            'recent_sales' => Sale::with(['customer:id,name'])->where('status', 'completed')->latest()->take(10)->get(),
            'top_products' => $this->productPerformanceReport($thisMonth . '-01', date('Y-m-t'))->take(5),
        ];
    }
}
