<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $query = Teacher::with('courses');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('specialization', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $teachers = $query->latest()->paginate(15)->withQueryString();

        return view('admin.teachers.index', compact('teachers'));
    }

    public function create()
    {
        return view('admin.teachers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,on_leave'],
            'create_user' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'required_if:create_user,1'],
        ]);

        $userId = null;
        if ($request->boolean('create_user') && $request->filled('email')) {
            $user = User::create([
                'name' => $data['full_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'teacher',
                'is_active' => true,
            ]);
            $user->assignRole('teacher');
            $userId = $user->id;
        }

        Teacher::create([
            'user_id' => $userId,
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'specialization' => $data['specialization'] ?? null,
            'qualification' => $data['qualification'] ?? null,
            'status' => $data['status'],
        ]);

        return redirect()->route('admin.teachers.index')
            ->with('success', __('messages.created_successfully'));
    }

    public function show(Teacher $teacher)
    {
        $teacher->load('courses');

        return view('admin.teachers.show', compact('teacher'));
    }

    public function edit(Teacher $teacher)
    {
        return view('admin.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,on_leave'],
        ]);

        $teacher->update($data);

        return redirect()->route('admin.teachers.index')
            ->with('success', __('messages.updated_successfully'));
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }

    public function toggle(Teacher $teacher)
    {
        $teacher->update([
            'status' => $teacher->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', __('messages.updated_successfully'));
    }
}
