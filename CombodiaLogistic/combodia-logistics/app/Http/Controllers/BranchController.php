<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::query();

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->is_active !== null) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $branches = $query->latest()->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return response()->json(['branches' => $branches]);
        }

        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        return view('branches.create');
    }

    public function store(StoreBranchRequest $request)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $branch = Branch::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Branch created successfully', 'branch' => $branch], 201);
        }

        return redirect()->route('branches.index')->with('success', 'Branch created successfully');
    }

    public function show(Branch $branch)
    {
        $branch->load(['warehouses', 'vehicles', 'drivers']);

        if (request()->ajax()) {
            return response()->json(['branch' => $branch]);
        }

        return view('branches.show', compact('branch'));
    }

    public function edit(Branch $branch)
    {
        return view('branches.edit', compact('branch'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', $branch->is_active);

        $branch->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Branch updated successfully', 'branch' => $branch]);
        }

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully');
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Branch deleted successfully']);
        }

        return redirect()->route('branches.index')->with('success', 'Branch deleted successfully');
    }
}
