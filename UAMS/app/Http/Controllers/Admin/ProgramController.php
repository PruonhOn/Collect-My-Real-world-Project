<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('permission:manage-system');

        $query = Program::with('department');

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $programs = $query->latest()->paginate(15)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('admin.programs.index', compact('programs', 'departments'));
    }

    public function create()
    {
        $this->authorize('permission:manage-system');

        $departments = Department::orderBy('name')->get();

        return view('admin.programs.create', compact('departments'));
    }

    public function store(StoreProgramRequest $request)
    {
        $this->authorize('permission:manage-system');

        Program::create($request->validated());

        return redirect()->route('admin.programs.index')
            ->with('success', 'Program created successfully.');
    }

    public function show(Program $program)
    {
        $this->authorize('permission:manage-system');

        $program->load(['department', 'courses']);

        return view('admin.programs.show', compact('program'));
    }

    public function edit(Program $program)
    {
        $this->authorize('permission:manage-system');

        $departments = Department::orderBy('name')->get();

        return view('admin.programs.edit', compact('program', 'departments'));
    }

    public function update(UpdateProgramRequest $request, Program $program)
    {
        $this->authorize('permission:manage-system');

        $program->update($request->validated());

        return redirect()->route('admin.programs.index')
            ->with('success', 'Program updated successfully.');
    }

    public function destroy(Program $program)
    {
        $this->authorize('permission:manage-system');

        if ($program->courses()->count() > 0) {
            return redirect()->route('admin.programs.index')
                ->with('error', 'Cannot delete program with associated courses.');
        }

        $program->delete();

        return redirect()->route('admin.programs.index')
            ->with('success', 'Program deleted successfully.');
    }
}
