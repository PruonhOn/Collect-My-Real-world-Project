<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinancialController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Transaction::with('creator:id,name');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $transactions = $query->latest()->paginate($request->per_page ?? 15);
        return $this->paginatedSuccess($transactions);
    }

    public function expenses(Request $request)
    {
        if ($request->isMethod('get')) {
            $query = Expense::with('creator:id,name');

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->has('date_from')) {
                $query->whereDate('expense_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('expense_date', '<=', $request->date_to);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $expenses = $query->latest()->paginate($request->per_page ?? 15);
            return $this->paginatedSuccess($expenses);
        }

        if ($request->isMethod('post')) {
            $validator = Validator::make($request->all(), [
                'category' => 'required|string|max:100',
                'expense_date' => 'required|date',
                'amount' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'reference_no' => 'nullable|string|max:100',
                'payment_method' => 'required|string|max:50',
                'status' => 'required|in:paid,pending,cancelled',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $data = $request->all();
            $data['created_by'] = auth()->id();

            $expense = Expense::create($data);

            Transaction::create([
                'transaction_no' => 'TXN-' . now()->format('YmdHis') . '-' . $expense->id,
                'type' => 'expense',
                'amount' => $expense->amount,
                'description' => $expense->description ?? 'Expense - ' . $expense->category,
                'transaction_date' => $expense->expense_date,
                'payment_method' => $expense->payment_method,
                'reference_type' => 'expense',
                'reference_id' => $expense->id,
                'status' => $expense->status === 'paid' ? 'completed' : 'pending',
                'created_by' => auth()->id(),
            ]);

            return $this->success($expense->load('creator:id,name'), 'Expense created successfully', 201);
        }

        return $this->error('Method not allowed', 405);
    }

    public function incomes(Request $request)
    {
        if ($request->isMethod('get')) {
            $query = Income::with('creator:id,name');

            if ($request->has('income_category')) {
                $query->where('income_category', $request->income_category);
            }

            if ($request->has('date_from')) {
                $query->whereDate('income_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('income_date', '<=', $request->date_to);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $incomes = $query->latest()->paginate($request->per_page ?? 15);
            return $this->paginatedSuccess($incomes);
        }

        if ($request->isMethod('post')) {
            $validator = Validator::make($request->all(), [
                'income_category' => 'required|string|max:100',
                'income_date' => 'required|date',
                'amount' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'reference_no' => 'nullable|string|max:100',
                'payment_method' => 'required|string|max:50',
                'status' => 'required|in:received,pending,cancelled',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $data = $request->all();
            $data['created_by'] = auth()->id();

            $income = Income::create($data);

            Transaction::create([
                'transaction_no' => 'TXN-' . now()->format('YmdHis') . '-' . $income->id,
                'type' => 'income',
                'amount' => $income->amount,
                'description' => $income->description ?? 'Income - ' . $income->income_category,
                'transaction_date' => $income->income_date,
                'payment_method' => $income->payment_method,
                'reference_type' => 'income',
                'reference_id' => $income->id,
                'status' => $income->status === 'received' ? 'completed' : 'pending',
                'created_by' => auth()->id(),
            ]);

            return $this->success($income->load('creator:id,name'), 'Income created successfully', 201);
        }

        return $this->error('Method not allowed', 405);
    }

    public function updateExpense(Request $request, int $id)
    {
        $expense = Expense::find($id);

        if (!$expense) {
            return $this->error('Expense not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'category' => 'sometimes|string|max:100',
            'expense_date' => 'sometimes|date',
            'amount' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'reference_no' => 'nullable|string|max:100',
            'payment_method' => 'sometimes|string|max:50',
            'status' => 'sometimes|in:paid,pending,cancelled',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $expense->update($request->all());

        Transaction::where('reference_type', 'expense')
            ->where('reference_id', $expense->id)
            ->update([
                'amount' => $expense->amount,
                'status' => $expense->status === 'paid' ? 'completed' : 'pending',
            ]);

        return $this->success($expense->fresh()->load('creator:id,name'), 'Expense updated successfully');
    }

    public function updateIncome(Request $request, int $id)
    {
        $income = Income::find($id);

        if (!$income) {
            return $this->error('Income not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'income_category' => 'sometimes|string|max:100',
            'income_date' => 'sometimes|date',
            'amount' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'reference_no' => 'nullable|string|max:100',
            'payment_method' => 'sometimes|string|max:50',
            'status' => 'sometimes|in:received,pending,cancelled',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $income->update($request->all());

        Transaction::where('reference_type', 'income')
            ->where('reference_id', $income->id)
            ->update([
                'amount' => $income->amount,
                'status' => $income->status === 'received' ? 'completed' : 'pending',
            ]);

        return $this->success($income->fresh()->load('creator:id,name'), 'Income updated successfully');
    }

    public function destroyExpense(int $id)
    {
        $expense = Expense::find($id);

        if (!$expense) {
            return $this->error('Expense not found', 404);
        }

        Transaction::where('reference_type', 'expense')
            ->where('reference_id', $expense->id)
            ->delete();

        $expense->delete();

        return $this->success(null, 'Expense deleted successfully');
    }

    public function destroyIncome(int $id)
    {
        $income = Income::find($id);

        if (!$income) {
            return $this->error('Income not found', 404);
        }

        Transaction::where('reference_type', 'income')
            ->where('reference_id', $income->id)
            ->delete();

        $income->delete();

        return $this->success(null, 'Income deleted successfully');
    }

    public function profitLoss(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $totalIncome = Transaction::where('type', 'income')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$request->start_date, $request->end_date])
            ->sum('amount');

        $totalExpense = Transaction::where('type', 'expense')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$request->start_date, $request->end_date])
            ->sum('amount');

        $netProfit = $totalIncome - $totalExpense;

        return $this->success([
            'period' => ['start' => $request->start_date, 'end' => $request->end_date],
            'total_income' => (float) $totalIncome,
            'total_expense' => (float) $totalExpense,
            'net_profit' => (float) $netProfit,
            'profit_margin' => $totalIncome > 0 ? round(($netProfit / $totalIncome) * 100, 2) : 0,
        ]);
    }

    public function cashFlow(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $transactions = Transaction::whereBetween('transaction_date', [$request->start_date, $request->end_date])
            ->orderBy('transaction_date')
            ->get()
            ->groupBy(function ($item) {
                return $item->transaction_date->format('Y-m-d');
            });

        $cashFlow = [];
        $runningBalance = 0;

        foreach ($transactions as $date => $txns) {
            $dayIncome = $txns->where('type', 'income')->sum('amount');
            $dayExpense = $txns->where('type', 'expense')->sum('amount');
            $runningBalance += ($dayIncome - $dayExpense);

            $cashFlow[] = [
                'date' => $date,
                'income' => (float) $dayIncome,
                'expense' => (float) $dayExpense,
                'net' => (float) ($dayIncome - $dayExpense),
                'balance' => (float) $runningBalance,
            ];
        }

        return $this->success([
            'period' => ['start' => $request->start_date, 'end' => $request->end_date],
            'opening_balance' => 0,
            'closing_balance' => (float) $runningBalance,
            'cash_flow' => $cashFlow,
        ]);
    }
}
