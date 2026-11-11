<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\BranchService;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService,
        protected BranchService $branchService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $customers = $this->customerService->getAll($request->only(['search', 'is_active']));
        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:customers,code',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'company' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'credit_limit' => 'nullable|numeric|min:0',
            'outstanding_balance' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $validated['created_by'] = auth()->id();
            $customer = $this->customerService->create($validated);

            $this->auditLogService->log('created', 'customers', "Customer created: {$customer->name}", $customer, null, $validated, auth()->id());

            return redirect()->route('customers.index')
                ->with('success', 'Customer created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create customer: ' . $e->getMessage())->withInput();
        }
    }

    public function show(int $id): View
    {
        $customer = $this->customerService->getById($id);
        $shipments = $this->customerService->getShipmentHistory($id);
        return view('customers.show', compact('customer', 'shipments'));
    }

    public function edit(int $id): View
    {
        $customer = $this->customerService->getById($id);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:customers,code,' . $id,
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:customers,email,' . $id,
            'company' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'credit_limit' => 'nullable|numeric|min:0',
            'outstanding_balance' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $customer = $this->customerService->update($id, $validated);

            $this->auditLogService->log('updated', 'customers', "Customer updated: {$customer->name}", $customer, null, $validated, auth()->id());

            return redirect()->route('customers.index')
                ->with('success', 'Customer updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update customer: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $customer = $this->customerService->getById($id);
            $this->customerService->delete($id);

            $this->auditLogService->log('deleted', 'customers', "Customer deleted: {$customer->name}", $customer, null, null, auth()->id());

            return redirect()->route('customers.index')
                ->with('success', 'Customer deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete customer: ' . $e->getMessage());
        }
    }
}
