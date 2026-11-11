<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Income;
use App\Models\IncomeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $query = Income::with(['incomeCategory', 'customer', 'branch']);

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('income_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->has('branch_id') && $request->branch_id !== null) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('income_category_id') && $request->income_category_id !== null) {
            $query->where('income_category_id', $request->income_category_id);
        }

        $incomes = $query->latest()->paginate(15)->withQueryString();
        $branches = Branch::where('is_active', true)->get();
        $incomeCategories = IncomeCategory::where('is_active', true)->get();

        if ($request->ajax()) {
            return response()->json(['incomes' => $incomes, 'branches' => $branches, 'incomeCategories' => $incomeCategories]);
        }

        return view('incomes.index', compact('incomes', 'branches', 'incomeCategories'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        $incomeCategories = IncomeCategory::where('is_active', true)->get();
        $customers = Customer::where('is_active', true)->get();
        return view('incomes.create', compact('branches', 'incomeCategories', 'customers'));
    }

    public function store(StoreIncomeRequest $request)
    {
        $validated = $request->validated();
        $validated['income_number'] = 'INC-' . strtoupper(Str::random(10));
        $validated['tax_amount'] = $validated['tax_amount'] ?? 0;
        $validated['total_amount'] = $validated['amount'] + $validated['tax_amount'];
        $validated['status'] = 'received';

        if ($request->hasFile('receipt_path')) {
            $validated['receipt_path'] = $request->file('receipt_path')->store('receipts', 'public');
        }

        $income = Income::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Income recorded successfully', 'income' => $income], 201);
        }

        return redirect()->route('incomes.index')->with('success', 'Income recorded successfully');
    }

    public function show(Income $income)
    {
        $income->load(['incomeCategory', 'customer', 'branch']);

        if (request()->ajax()) {
            return response()->json(['income' => $income]);
        }

        return view('incomes.show', compact('income'));
    }

    public function edit(Income $income)
    {
        $branches = Branch::where('is_active', true)->get();
        $incomeCategories = IncomeCategory::where('is_active', true)->get();
        $customers = Customer::where('is_active', true)->get();
        return view('incomes.edit', compact('income', 'branches', 'incomeCategories', 'customers'));
    }

    public function update(Request $request, Income $income)
    {
        $validated = $request->validate([
            'income_category_id' => 'required|exists:income_categories,id',
            'customer_id' => 'nullable|exists:customers,id',
            'branch_id' => 'nullable|exists:branches,id',
            'description' => 'required|string|max:1000',
            'amount' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'income_date' => 'required|date',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['tax_amount'] = $validated['tax_amount'] ?? 0;
        $validated['total_amount'] = $validated['amount'] + $validated['tax_amount'];

        if ($request->hasFile('receipt_path')) {
            $validated['receipt_path'] = $request->file('receipt_path')->store('receipts', 'public');
        }

        $income->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Income updated successfully', 'income' => $income]);
        }

        return redirect()->route('incomes.index')->with('success', 'Income updated successfully');
    }

    public function destroy(Income $income)
    {
        $income->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Income deleted successfully']);
        }

        return redirect()->route('incomes.index')->with('success', 'Income deleted successfully');
    }
}
