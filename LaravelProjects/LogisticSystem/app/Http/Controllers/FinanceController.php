<?php

namespace App\Http\Controllers;

use App\Services\FinanceService;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct(
        protected FinanceService $financeService
    ) {}

    public function index(): View
    {
        $overview = $this->financeService->getOverview();
        $revenueChart = $this->financeService->getRevenueChartData();
        $expensesByCategory = $this->financeService->getExpenseByCategory();
        $revenueByBranch = $this->financeService->getRevenueByBranch();

        return view('finance.index', compact(
            'overview', 'revenueChart', 'expensesByCategory', 'revenueByBranch'
        ));
    }

    public function transactions()
    {
        return redirect()->route('transactions.index');
    }

    public function expenses()
    {
        return redirect()->route('expenses.index');
    }
}
