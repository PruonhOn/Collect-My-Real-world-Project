<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['expenseCategory', 'branch', 'user', 'approvedBy']);

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('expense_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->has('branch_id') && $request->branch_id !== null) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('expense_category_id') && $request->expense_category_id !== null) {
            $query->where('expense_category_id', $request->expense_category_id);
        }

        $expenses = $query->latest()->paginate(15)->withQueryString();
        $branches = Branch::where('is_active', true)->get();
        $expenseCategories = ExpenseCategory::where('is_active', true)->get();

        if ($request->ajax()) {
            return response()->json(['expenses' => $expenses, 'branches' => $branches, 'expenseCategories' => $expenseCategories]);
        }

        return view('expenses.index', compact('expenses', 'branches', 'expenseCategories'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        $expenseCategories = ExpenseCategory::where('is_active', true)->get();
        return view('expenses.create', compact('branches', 'expenseCategories'));
    }

    public function store(StoreExpenseRequest $request)
    {
        $validated = $request->validated();
        $validated['expense_number'] = 'EXP-' . strtoupper(Str::random(10));
        $validated['user_id'] = auth()->id();
        $validated['tax_amount'] = $validated['tax_amount'] ?? 0;
        $validated['total_amount'] = $validated['amount'] + $validated['tax_amount'];
        $validated['status'] = 'pending';

        if ($request->hasFile('receipt_path')) {
            $validated['receipt_path'] = $request->file('receipt_path')->store('receipts', 'public');
        }

        $expense = Expense::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Expense created successfully', 'expense' => $expense], 201);
        }

        return redirect()->route('expenses.index')->with('success', 'Expense created successfully');
    }

    public function show(Expense $expense)
    {
        $expense->load(['expenseCategory', 'branch', 'user', 'approvedBy']);

        if (request()->ajax()) {
            return response()->json(['expense' => $expense]);
        }

        return view('expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        $branches = Branch::where('is_active', true)->get();
        $expenseCategories = ExpenseCategory::where('is_active', true)->get();
        return view('expenses.edit', compact('expense', 'branches', 'expenseCategories'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        $validated = $request->validated();
        $validated['tax_amount'] = $validated['tax_amount'] ?? 0;
        $validated['total_amount'] = $validated['amount'] + $validated['tax_amount'];

        if ($request->hasFile('receipt_path')) {
            $validated['receipt_path'] = $request->file('receipt_path')->store('receipts', 'public');
        }

        $expense->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Expense updated successfully', 'expense' => $expense]);
        }

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Expense deleted successfully']);
        }

        return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully');
    }

    public function approve(Expense $expense)
    {
        $expense->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        if (request()->ajax()) {
            return response()->json(['message' => 'Expense approved successfully', 'expense' => $expense]);
        }

        return back()->with('success', 'Expense approved successfully');
    }
}
