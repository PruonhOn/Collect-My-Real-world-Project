<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Models\Package;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Models\WarehouseShelf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $query = Package::with(['shipment', 'warehouse', 'warehouseShelf']);

        if ($request->has('shipment_id') && $request->shipment_id !== null) {
            $query->where('shipment_id', $request->shipment_id);
        }

        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('package_number', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $packages = $query->latest()->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return response()->json(['packages' => $packages]);
        }

        return view('packages.index', compact('packages'));
    }

    public function create()
    {
        $shipments = Shipment::latest()->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $shelves = WarehouseShelf::where('is_active', true)->get();
        return view('packages.create', compact('shipments', 'warehouses', 'shelves'));
    }

    public function store(StorePackageRequest $request)
    {
        $validated = $request->validated();
        $validated['package_number'] = 'PKG-' . strtoupper(Str::random(8));
        $validated['barcode'] = $validated['barcode'] ?? 'BC-' . strtoupper(Str::random(12));
        $validated['status'] = 'pending';

        $package = Package::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Package created successfully', 'package' => $package], 201);
        }

        return redirect()->route('packages.index')->with('success', 'Package created successfully');
    }

    public function show(Package $package)
    {
        $package->load(['shipment', 'warehouse', 'warehouseShelf']);

        if (request()->ajax()) {
            return response()->json(['package' => $package]);
        }

        return view('packages.show', compact('package'));
    }

    public function edit(Package $package)
    {
        $shipments = Shipment::latest()->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $shelves = WarehouseShelf::where('is_active', true)->get();
        return view('packages.edit', compact('package', 'shipments', 'warehouses', 'shelves'));
    }

    public function update(UpdatePackageRequest $request, Package $package)
    {
        $validated = $request->validated();
        $package->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Package updated successfully', 'package' => $package]);
        }

        return redirect()->route('packages.index')->with('success', 'Package updated successfully');
    }

    public function destroy(Package $package)
    {
        $package->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Package deleted successfully']);
        }

        return redirect()->route('packages.index')->with('success', 'Package deleted successfully');
    }

    public function generateBarcode(Package $package)
    {
        $package->update(['barcode' => 'BC-' . strtoupper(Str::random(12))]);

        if (request()->ajax()) {
            return response()->json(['message' => 'Barcode generated successfully', 'package' => $package]);
        }

        return back()->with('success', 'Barcode generated successfully');
    }
}
