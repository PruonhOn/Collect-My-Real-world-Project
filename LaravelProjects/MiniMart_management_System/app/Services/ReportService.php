<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getDailyReport()
    {
        $date = today();
        return [
            'sales' => Sale::whereDate('created_at', $date)->sum('total_amount'),
            'expenses' => Expense::whereDate('date', $date)->sum('amount'),
            'profit' => $this->calculateProfit('daily'),
            'sales_count' => Sale::whereDate('created_at', $date)->count(),
        ];
    }

    public function getWeeklyReport()
    {
        $start = now()->startOfWeek();
        $end = now()->endOfWeek();
        return [
            'sales' => Sale::whereBetween('created_at', [$start, $end])->sum('total_amount'),
            'expenses' => Expense::whereBetween('date', [$start, $end])->sum('amount'),
            'profit' => $this->calculateProfit('weekly'),
            'sales_count' => Sale::whereBetween('created_at', [$start, $end])->count(),
        ];
    }

    public function getMonthlyReport()
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        return [
            'sales' => Sale::whereBetween('created_at', [$start, $end])->sum('total_amount'),
            'expenses' => Expense::whereBetween('date', [$start, $end])->sum('amount'),
            'profit' => $this->calculateProfit('monthly'),
            'sales_count' => Sale::whereBetween('created_at', [$start, $end])->count(),
        ];
    }

    public function getCustomReport($startDate, $endDate)
    {
        return [
            'sales' => Sale::whereBetween('created_at', [$startDate, $endDate])->sum('total_amount'),
            'expenses' => Expense::whereBetween('date', [$startDate, $endDate])->sum('amount'),
            'profit' => $this->calculateProfit('custom', $startDate, $endDate),
            'sales_count' => Sale::whereBetween('created_at', [$startDate, $endDate])->count(),
        ];
    }

    public function getDashboardStats()
    {
        $today = today();
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return [
            'total_products' => \App\Models\Product::count(),
            'total_categories' => \App\Models\Category::count(),
            'total_stock' => \App\Models\Product::sum('quantity'),
            'today_sales' => Sale::whereDate('created_at', $today)->sum('total_amount'),
            'weekly_sales' => Sale::whereBetween('created_at', [$startOfWeek, $endOfWeek])->sum('total_amount'),
            'monthly_sales' => Sale::whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('total_amount'),
            'total_expenses' => Expense::whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->sum('amount'),
            'today_expenses' => Expense::whereDate('date', $today)->sum('amount'),
            'monthly_expenses' => Expense::whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->sum('amount'),
            'net_profit' => $this->calculateProfit('monthly'),
            'low_stock_count' => \App\Models\Product::where('quantity', '<', 10)->count(),
            'recent_sales' => Sale::with('items.product')->latest()->take(5)->get(),
        ];
    }

    public function getChartData()
    {
        $days = [];
        $salesData = [];
        $expensesData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $days[] = $date->format('D');
            $salesData[] = Sale::whereDate('created_at', $date)->sum('total_amount');
            $expensesData[] = Expense::whereDate('date', $date)->sum('amount');
        }

        return [
            'days' => $days,
            'sales' => $salesData,
            'expenses' => $expensesData,
        ];
    }

    private function calculateProfit($period, $startDate = null, $endDate = null)
    {
        $salesQuery = Sale::query();
        $expenseQuery = Expense::query();

        switch ($period) {
            case 'daily':
                $salesQuery->whereDate('created_at', today());
                $expenseQuery->whereDate('date', today());
                break;
            case 'weekly':
                $salesQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                $expenseQuery->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'monthly':
                $salesQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                $expenseQuery->whereMonth('date', now()->month)->whereYear('date', now()->year);
                break;
            case 'custom':
                $salesQuery->whereBetween('created_at', [$startDate, $endDate]);
                $expenseQuery->whereBetween('date', [$startDate, $endDate]);
                break;
        }

        $totalSales = $salesQuery->sum('total_amount');
        $totalExpenses = $expenseQuery->sum('amount');

        $costOfGoods = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->select(DB::raw('SUM(sale_items.quantity * products.cost_price) as total_cost'));

        switch ($period) {
            case 'daily':
                $costOfGoods->whereDate('sales.created_at', today());
                break;
            case 'weekly':
                $costOfGoods->whereBetween('sales.created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'monthly':
                $costOfGoods->whereMonth('sales.created_at', now()->month)->whereYear('sales.created_at', now()->year);
                break;
            case 'custom':
                $costOfGoods->whereBetween('sales.created_at', [$startDate, $endDate]);
                break;
        }

        $cogs = $costOfGoods->value('total_cost') ?? 0;

        return $totalSales - $cogs - $totalExpenses;
    }
}
