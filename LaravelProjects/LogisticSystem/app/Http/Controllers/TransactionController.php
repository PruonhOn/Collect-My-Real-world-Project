<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\BranchService;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected BranchService $branchService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $transactions = $this->transactionService->getAll($request->only([
            'search', 'type', 'category', 'branch_id', 'date_from', 'date_to',
        ]));
        $types = $this->transactionService->getTypes();
        $categories = $this->transactionService->getCategories();
        return view('transactions.index', compact('transactions', 'types', 'categories'));
    }

    public function create(): View
    {
        $branches = $this->branchService->getActiveBranches();
        $types = $this->transactionService->getTypes();
        $categories = $this->transactionService->getCategories();
        return view('transactions.create', compact('branches', 'types', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'type' => 'required|string|max:50',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'payment_method' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'reference' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        try {
            $validated['created_by'] = auth()->id();
            $transaction = $this->transactionService->create($validated);

            $this->auditLogService->log('created', 'transactions', "Transaction created: {$transaction->transaction_number}", $transaction, null, $validated, auth()->id());

            return redirect()->route('transactions.index')
                ->with('success', "Transaction {$transaction->transaction_number} created successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create transaction: ' . $e->getMessage())->withInput();
        }
    }

    public function show(int $id): View
    {
        $transaction = $this->transactionService->getById($id);
        return view('transactions.show', compact('transaction'));
    }

    public function edit(int $id): View
    {
        $transaction = $this->transactionService->getById($id);
        $branches = $this->branchService->getActiveBranches();
        $types = $this->transactionService->getTypes();
        $categories = $this->transactionService->getCategories();
        return view('transactions.edit', compact('transaction', 'branches', 'types', 'categories'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'type' => 'required|string|max:50',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'payment_method' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'reference' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        try {
            $transaction = $this->transactionService->update($id, $validated);

            $this->auditLogService->log('updated', 'transactions', "Transaction updated: {$transaction->transaction_number}", $transaction, null, $validated, auth()->id());

            return redirect()->route('transactions.index')
                ->with('success', "Transaction {$transaction->transaction_number} updated successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update transaction: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $transaction = $this->transactionService->getById($id);
            $this->transactionService->delete($id);

            $this->auditLogService->log('deleted', 'transactions', "Transaction deleted: {$transaction->transaction_number}", $transaction, null, null, auth()->id());

            return redirect()->route('transactions.index')
                ->with('success', 'Transaction deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete transaction: ' . $e->getMessage());
        }
    }
}
