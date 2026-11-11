<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ParentController extends Controller
{
    public function index(Request $request)
    {
        $query = ParentGuardian::with('children');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $parents = $query->latest()->paginate(15)->withQueryString();

        return view('admin.parents.index', compact('parents'));
    }

    public function create()
    {
        $children = Student::orderBy('full_name')->get();

        return view('admin.parents.create', compact('children'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'create_user' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'required_if:create_user,1'],
            'children_ids' => ['nullable', 'array'],
            'children_ids.*' => ['exists:students,id'],
        ]);

        $userId = null;
        if ($request->boolean('create_user') && $request->filled('email')) {
            $user = User::create([
                'name' => $data['full_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'parent',
                'is_active' => true,
            ]);
            $user->assignRole('parent');
            $userId = $user->id;
        }

        $parent = ParentGuardian::create([
            'user_id' => $userId,
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'occupation' => $data['occupation'] ?? null,
            'is_active' => true,
        ]);

        if ($request->filled('children_ids')) {
            foreach ($request->children_ids as $childId) {
                $parent->children()->attach($childId, ['relation' => 'អាណាព្យាបាល']);
            }
        }

        return redirect()->route('admin.parents.index')
            ->with('success', __('messages.created_successfully'));
    }

    public function show(ParentGuardian $parent)
    {
        $parent->load('children.courses');

        return view('admin.parents.show', compact('parent'));
    }

    public function edit(ParentGuardian $parent)
    {
        $children = Student::orderBy('full_name')->get();
        $parent->load('children');

        return view('admin.parents.edit', compact('parent', 'children'));
    }

    public function update(Request $request, ParentGuardian $parent)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'children_ids' => ['nullable', 'array'],
            'children_ids.*' => ['exists:students,id'],
        ]);

        $parent->update([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'occupation' => $data['occupation'] ?? null,
        ]);

        if ($request->has('children_ids')) {
            $parent->children()->sync($request->children_ids);
        }

        return redirect()->route('admin.parents.index')
            ->with('success', __('messages.updated_successfully'));
    }

    public function destroy(ParentGuardian $parent)
    {
        $parent->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }

    public function toggle(ParentGuardian $parent)
    {
        $parent->update(['is_active' => ! $parent->is_active]);

        return back()->with('success', __('messages.updated_successfully'));
    }
}
