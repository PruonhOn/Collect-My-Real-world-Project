<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\BranchService;
use App\Services\ExpenseService;
use App\Services\VehicleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService,
        protected BranchService $branchService,
        protected VehicleService $vehicleService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $expenses = $this->expenseService->getAll($request->only([
            'search', 'category', 'branch_id', 'vehicle_id', 'status', 'date_from', 'date_to',
        ]));
        $categories = $this->expenseService->getCategories();
        return view('expenses.index', compact('expenses', 'categories'));
    }

    public function create(): View
    {
        $branches = $this->branchService->getActiveBranches();
        $vehicles = $this->vehicleService->getActiveVehicles();
        $categories = $this->expenseService->getCategories();
        return view('expenses.create', compact('branches', 'vehicles', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_date' => 'required|date',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:1000',
            'vendor' => 'nullable|string|max:255',
            'receipt_path' => 'nullable|image|max:5120',
            'is_recurring' => 'boolean',
            'status' => 'required|string|max:50',
            'branch_id' => 'nullable|exists:branches,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ]);

        try {
            $validated['is_recurring'] = $request->boolean('is_recurring');
            $validated['created_by'] = auth()->id();
            $expense = $this->expenseService->create($validated);

            $this->auditLogService->log('created', 'expenses', "Expense created: {$expense->expense_number}", $expense, null, $validated, auth()->id());

            return redirect()->route('expenses.index')
                ->with('success', "Expense {$expense->expense_number} created successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create expense: ' . $e->getMessage())->withInput();
        }
    }

    public function show(int $id): View
    {
        $expense = $this->expenseService->getById($id);
        return view('expenses.show', compact('expense'));
    }

    public function edit(int $id): View
    {
        $expense = $this->expenseService->getById($id);
        $branches = $this->branchService->getActiveBranches();
        $vehicles = $this->vehicleService->getActiveVehicles();
        $categories = $this->expenseService->getCategories();
        return view('expenses.edit', compact('expense', 'branches', 'vehicles', 'categories'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'expense_date' => 'required|date',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:1000',
            'vendor' => 'nullable|string|max:255',
            'receipt_path' => 'nullable|image|max:5120',
            'is_recurring' => 'boolean',
            'status' => 'required|string|max:50',
            'branch_id' => 'nullable|exists:branches,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ]);

        try {
            $validated['is_recurring'] = $request->boolean('is_recurring');
            $expense = $this->expenseService->update($id, $validated);

            $this->auditLogService->log('updated', 'expenses', "Expense updated: {$expense->expense_number}", $expense, null, $validated, auth()->id());

            return redirect()->route('expenses.index')
                ->with('success', "Expense {$expense->expense_number} updated successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update expense: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $expense = $this->expenseService->getById($id);
            $this->expenseService->delete($id);

            $this->auditLogService->log('deleted', 'expenses', "Expense deleted: {$expense->expense_number}", $expense, null, null, auth()->id());

            return redirect()->route('expenses.index')
                ->with('success', 'Expense deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete expense: ' . $e->getMessage());
        }
    }
}
