<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Sale;
use App\Models\Purchase;
use Illuminate\Http\Request;

class FinancialController extends Controller
{
    public function index(Request $request)
    {
        $expenses = Expense::latest()->paginate(20);
        $incomes = Income::latest()->paginate(20);

        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

        $totalSales = Sale::whereBetween('sale_date', [$startDate, $endDate])->where('status', 'completed')->sum('grand_total');
        $totalPurchases = Purchase::whereBetween('purchase_date', [$startDate, $endDate])->where('status', 'received')->sum('grand_total');
        $totalExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->where('status', 'paid')->sum('amount');

        $grossProfit = $totalSales - $totalPurchases;
        $netProfit = $grossProfit - $totalExpenses;
        $profitMargin = $totalSales > 0 ? ($netProfit / $totalSales) * 100 : 0;

        return view('financial.index', compact(
            'expenses', 'incomes',
            'totalSales', 'totalPurchases', 'totalExpenses',
            'grossProfit', 'netProfit', 'profitMargin',
            'startDate', 'endDate'
        ));
    }

    public function expensesStore(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:paid,pending,cancelled',
        ]);

        $method = $request->method();
        if ($method === 'PUT' && $request->route('id')) {
            $expense = Expense::findOrFail($request->route('id'));
            $expense->update([
                'category' => $validated['category'],
                'expense_date' => $validated['date'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'description' => $validated['description'] ?? '',
                'status' => $validated['status'] ?? 'paid',
            ]);
            return response()->json(['message' => 'Expense updated successfully']);
        }

        Expense::create([
            'category' => $validated['category'],
            'expense_date' => $validated['date'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'description' => $validated['description'] ?? '',
            'status' => $validated['status'] ?? 'paid',
            'created_by' => auth()->id(),
        ]);

        return response()->json(['message' => 'Expense added successfully']);
    }

    public function incomesStore(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:paid,pending,cancelled',
        ]);

        $method = $request->method();
        if ($method === 'PUT' && $request->route('id')) {
            $income = Income::findOrFail($request->route('id'));
            $income->update([
                'income_category' => $validated['category'],
                'income_date' => $validated['date'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'description' => $validated['description'] ?? '',
                'status' => $validated['status'] ?? 'paid',
            ]);
            return response()->json(['message' => 'Income updated successfully']);
        }

        Income::create([
            'income_category' => $validated['category'],
            'income_date' => $validated['date'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'description' => $validated['description'] ?? '',
            'status' => $validated['status'] ?? 'paid',
            'created_by' => auth()->id(),
        ]);

        return response()->json(['message' => 'Income added successfully']);
    }

    public function pnl(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->endOfMonth()->format('Y-m-d'));

        $totalRevenue = Sale::whereBetween('sale_date', [$from, $to])->where('status', 'completed')->sum('grand_total');
        $totalExpenses = Expense::whereBetween('expense_date', [$from, $to])->where('status', 'paid')->sum('amount');

        $incomes = Income::whereBetween('income_date', [$from, $to])->where('status', 'paid')->get();
        $expenses = Expense::whereBetween('expense_date', [$from, $to])->where('status', 'paid')->get();

        $transactions = collect();

        foreach ($incomes as $inc) {
            $transactions->push([
                'type' => 'income',
                'date' => $inc->income_date,
                'category' => $inc->income_category,
                'description' => $inc->description,
                'amount' => $inc->amount,
            ]);
        }
        foreach ($expenses as $exp) {
            $transactions->push([
                'type' => 'expense',
                'date' => $exp->expense_date,
                'category' => $exp->category,
                'description' => $exp->description,
                'amount' => $exp->amount,
            ]);
        }

        $totalIncomeAmount = $incomes->sum('amount');

        return response()->json([
            'total_revenue' => $totalRevenue + $totalIncomeAmount,
            'total_expenses' => $totalExpenses,
            'transactions' => $transactions->sortByDesc('date')->values(),
        ]);
    }
}
