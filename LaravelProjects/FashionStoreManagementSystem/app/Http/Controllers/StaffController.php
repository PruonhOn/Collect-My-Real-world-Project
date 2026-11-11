<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StaffStoreRequest;
use App\Http\Requests\StaffUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $staff = User::query()
            ->withCount('orders')
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim($request->input('q'));
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->input('role')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('staff.index', compact('staff'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $roles = Role::cases();

        return view('staff.create', compact('roles'));
    }

    public function store(StaffStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = User::create($request->validated());

        return redirect()
            ->route('staff.index')
            ->with('success', 'Staff account for "'.$user->name.'" created.');
    }

    public function edit(User $staff): View
    {
        $this->authorize('update', $staff);

        $roles = Role::cases();

        return view('staff.edit', compact('staff', 'roles'));
    }

    public function update(StaffUpdateRequest $request, User $staff): RedirectResponse
    {
        $this->authorize('update', $staff);

        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $staff->update($data);

        return redirect()
            ->route('staff.index')
            ->with('success', 'Staff account updated.');
    }

    public function toggleActive(User $staff): RedirectResponse
    {
        $this->authorize('deactivate', $staff);

        $staff->update(['is_active' => ! $staff->is_active]);

        $state = $staff->is_active ? 'activated' : 'deactivated';

        return back()->with('success', 'Staff account "'.$staff->name.'" '.$state.'.');
    }

    public function destroy(User $staff): RedirectResponse
    {
        $this->authorize('delete', $staff);

        $staff->delete();

        return redirect()
            ->route('staff.index')
            ->with('success', 'Staff account deleted.');
    }
}
