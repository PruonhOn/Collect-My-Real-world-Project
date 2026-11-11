<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    public function getOverview(): array
    {
        $totalInvoiced = Invoice::sum('total');
        $totalPaid = Invoice::sum('paid_amount');
        $totalOutstanding = Invoice::sum('balance');
        $totalExpenses = Expense::sum('amount');
        $netRevenue = $totalPaid - $totalExpenses;

        return [
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'total_outstanding' => $totalOutstanding,
            'total_expenses' => $totalExpenses,
            'net_revenue' => $netRevenue,
            'invoice_count' => Invoice::count(),
            'paid_invoices' => Invoice::where('status', 'paid')->count(),
            'unpaid_invoices' => Invoice::whereIn('status', ['unpaid', 'partial'])->count(),
            'overdue_invoices' => Invoice::where('status', 'unpaid')
                ->where('due_date', '<', now())
                ->count(),
            'monthly_revenue' => Invoice::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total'),
            'monthly_expenses' => Expense::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
        ];
    }

    public function getRevenueChartData(int $months = 12): array
    {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $data[] = [
                'month' => $date->format('M Y'),
                'revenue' => Invoice::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total'),
                'expenses' => Expense::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('amount'),
                'collected' => Invoice::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('paid_amount'),
            ];
        }
        return $data;
    }

    public function getExpenseByCategory(): array
    {
        return Expense::select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();
    }

    public function getRevenueByBranch(): array
    {
        return Invoice::select('branch_id', DB::raw('SUM(total) as total'))
            ->with('branch:id,name')
            ->groupBy('branch_id')
            ->get()
            ->map(function ($item) {
                return [
                    'branch' => $item->branch?->name ?? 'N/A',
                    'total' => $item->total,
                ];
            })
            ->toArray();
    }
}
