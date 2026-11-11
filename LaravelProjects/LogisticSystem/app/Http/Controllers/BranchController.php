<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(
        protected BranchService $branchService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $branches = $this->branchService->getAll($request->only(['search', 'is_active']));
        return view('branches.index', compact('branches'));
    }

    public function create(): View
    {
        return view('branches.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:branches,code',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager_name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $validated['created_by'] = auth()->id();
            $branch = $this->branchService->create($validated);

            $this->auditLogService->log('created', 'branches', "Branch created: {$branch->name}", $branch, null, $validated, auth()->id());

            return redirect()->route('branches.index')
                ->with('success', 'Branch created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create branch: ' . $e->getMessage())->withInput();
        }
    }

    public function show(int $id): View
    {
        $branch = $this->branchService->getById($id);
        return view('branches.show', compact('branch'));
    }

    public function edit(int $id): View
    {
        $branch = $this->branchService->getById($id);
        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:branches,code,' . $id,
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager_name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $branch = $this->branchService->update($id, $validated);

            $this->auditLogService->log('updated', 'branches', "Branch updated: {$branch->name}", $branch, null, $validated, auth()->id());

            return redirect()->route('branches.index')
                ->with('success', 'Branch updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update branch: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $branch = $this->branchService->getById($id);
            $this->branchService->delete($id);

            $this->auditLogService->log('deleted', 'branches', "Branch deleted: {$branch->name}", $branch, null, null, auth()->id());

            return redirect()->route('branches.index')
                ->with('success', 'Branch deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete branch: ' . $e->getMessage());
        }
    }
}
