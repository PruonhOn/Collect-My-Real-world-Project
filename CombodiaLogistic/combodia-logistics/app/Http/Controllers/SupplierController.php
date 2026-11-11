<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->is_active !== null) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $suppliers = $query->latest()->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return response()->json(['suppliers' => $suppliers]);
        }

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $supplier = Supplier::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Supplier created successfully', 'supplier' => $supplier], 201);
        }

        return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully');
    }

    public function show(Supplier $supplier)
    {
        if (request()->ajax()) {
            return response()->json(['supplier' => $supplier]);
        }

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', $supplier->is_active);

        $supplier->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Supplier updated successfully', 'supplier' => $supplier]);
        }

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Supplier deleted successfully']);
        }

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully');
    }
}
