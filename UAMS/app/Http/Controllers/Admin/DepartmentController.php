<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('permission:manage-system');

        $query = Department::with('faculty');

        if ($request->filled('faculty_id')) {
            $query->where('faculty_id', $request->input('faculty_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $departments = $query->latest()->paginate(15)->withQueryString();
        $faculties = Faculty::orderBy('name')->get();

        return view('admin.departments.index', compact('departments', 'faculties'));
    }

    public function create()
    {
        $this->authorize('permission:manage-system');

        $faculties = Faculty::orderBy('name')->get();

        return view('admin.departments.create', compact('faculties'));
    }

    public function store(StoreDepartmentRequest $request)
    {
        $this->authorize('permission:manage-system');

        Department::create($request->validated());

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function show(Department $department)
    {
        $this->authorize('permission:manage-system');

        $department->load(['faculty', 'programs']);

        return view('admin.departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        $this->authorize('permission:manage-system');

        $faculties = Faculty::orderBy('name')->get();

        return view('admin.departments.edit', compact('department', 'faculties'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $this->authorize('permission:manage-system');

        $department->update($request->validated());

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        $this->authorize('permission:manage-system');

        if ($department->programs()->count() > 0) {
            return redirect()->route('admin.departments.index')
                ->with('error', 'Cannot delete department with associated programs.');
        }

        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
