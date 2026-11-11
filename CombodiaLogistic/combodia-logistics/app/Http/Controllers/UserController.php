<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('role') && $request->role !== null) {
            $query->role($request->role);
        }

        if ($request->has('is_active') && $request->is_active !== null) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $roles = Role::all();

        if ($request->ajax()) {
            return response()->json(['users' => $users, 'roles' => $roles]);
        }

        return view('users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        if ($request->has('role')) {
            $user->assignRole($request->role);
        }

        if ($request->ajax()) {
            return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
        }

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    public function show(User $user)
    {
        $user->load('roles');

        if ($request()->ajax()) {
            return response()->json(['user' => $user]);
        }

        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $user->load('roles');
        $roles = Role::all();
        $userRole = $user->roles->first();

        return view('users.edit', compact('user', 'roles', 'userRole'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        if ($request->has('role')) {
            $user->syncRoles([$request->role]);
        }

        if ($request->ajax()) {
            return response()->json(['message' => 'User updated successfully', 'user' => $user]);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully');
    }

    public function destroy(User $user)
    {
        $user->delete();

        if ($request()->ajax()) {
            return response()->json(['message' => 'User deleted successfully']);
        }

        return redirect()->route('users.index')->with('success', 'User deleted successfully');
    }
}
