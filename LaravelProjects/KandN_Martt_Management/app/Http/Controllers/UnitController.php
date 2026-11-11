<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::latest()->paginate(20);
        return view('units.index', compact('units'));
    }

    public function create()
    {
        return view('units.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        Unit::create($validated);

        return redirect()->route('units.index')->with('success', 'Unit created successfully');
    }

    public function show(Unit $unit)
    {
        $unit->load('products');
        return view('units.show', compact('unit'));
    }

    public function edit(Unit $unit)
    {
        return view('units.form', compact('unit'));
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $unit->update($validated);

        return redirect()->route('units.index')->with('success', 'Unit updated successfully');
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();
        return redirect()->route('units.index')->with('success', 'Unit deleted successfully');
    }
}
