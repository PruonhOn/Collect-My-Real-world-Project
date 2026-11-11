<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFacultyRequest;
use App\Http\Requests\UpdateFacultyRequest;
use App\Models\Faculty;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('permission:manage-system');

        $query = Faculty::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $faculties = $query->latest()->paginate(15)->withQueryString();

        return view('admin.faculties.index', compact('faculties'));
    }

    public function create()
    {
        $this->authorize('permission:manage-system');

        return view('admin.faculties.create');
    }

    public function store(StoreFacultyRequest $request)
    {
        $this->authorize('permission:manage-system');

        Faculty::create($request->validated());

        return redirect()->route('admin.faculties.index')
            ->with('success', 'Faculty created successfully.');
    }

    public function show(Faculty $faculty)
    {
        $this->authorize('permission:manage-system');

        $faculty->load(['departments']);

        return view('admin.faculties.show', compact('faculty'));
    }

    public function edit(Faculty $faculty)
    {
        $this->authorize('permission:manage-system');

        return view('admin.faculties.edit', compact('faculty'));
    }

    public function update(UpdateFacultyRequest $request, Faculty $faculty)
    {
        $this->authorize('permission:manage-system');

        $faculty->update($request->validated());

        return redirect()->route('admin.faculties.index')
            ->with('success', 'Faculty updated successfully.');
    }

    public function destroy(Faculty $faculty)
    {
        $this->authorize('permission:manage-system');

        if ($faculty->departments()->count() > 0) {
            return redirect()->route('admin.faculties.index')
                ->with('error', 'Cannot delete faculty with associated departments.');
        }

        $faculty->delete();

        return redirect()->route('admin.faculties.index')
            ->with('success', 'Faculty deleted successfully.');
    }
}
