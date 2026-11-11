<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\AuditLogService;
use App\Services\BranchService;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected RoleService $roleService,
        protected BranchService $branchService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $users = $this->userService->getAll($request->only(['search', 'is_active']));
        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::all();
        $branches = $this->branchService->getActiveBranches();
        return view('admin.users.create', compact('roles', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'branches' => 'nullable|array',
            'branches.*' => 'exists:branches,id',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $user = $this->userService->create($validated);

            $this->auditLogService->log('created', 'users', "User created: {$user->name}", $user, null, $validated, auth()->id());

            return redirect()->route('admin.users.index')
                ->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create user: ' . $e->getMessage())->withInput();
        }
    }

    public function show(int $id): View
    {
        $user = $this->userService->getById($id);
        return view('admin.users.show', compact('user'));
    }

    public function edit(int $id): View
    {
        $user = $this->userService->getById($id);
        $roles = Role::all();
        $branches = $this->branchService->getActiveBranches();
        return view('admin.users.edit', compact('user', 'roles', 'branches'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'branches' => 'nullable|array',
            'branches.*' => 'exists:branches,id',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $user = $this->userService->update($id, $validated);

            $this->auditLogService->log('updated', 'users', "User updated: {$user->name}", $user, null, $validated, auth()->id());

            return redirect()->route('admin.users.index')
                ->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update user: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $user = $this->userService->getById($id);
            $this->userService->delete($id);

            $this->auditLogService->log('deleted', 'users', "User deleted: {$user->name}", $user, null, null, auth()->id());

            return redirect()->route('admin.users.index')
                ->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }
}
