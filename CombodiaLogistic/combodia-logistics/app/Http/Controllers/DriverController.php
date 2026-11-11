<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $query = Driver::with(['branch', 'vehicle']);

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('license_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('branch_id') && $request->branch_id !== null) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        $drivers = $query->latest()->paginate(15)->withQueryString();
        $branches = Branch::where('is_active', true)->get();
        $vehicles = Vehicle::where('is_active', true)->get();

        if ($request->ajax()) {
            return response()->json(['drivers' => $drivers, 'branches' => $branches, 'vehicles' => $vehicles]);
        }

        return view('drivers.index', compact('drivers', 'branches', 'vehicles'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        $vehicles = Vehicle::where('is_active', true)->get();
        $users = User::whereDoesntHave('driver')->get();
        return view('drivers.create', compact('branches', 'vehicles', 'users'));
    }

    public function store(StoreDriverRequest $request)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $driver = Driver::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Driver created successfully', 'driver' => $driver], 201);
        }

        return redirect()->route('drivers.index')->with('success', 'Driver created successfully');
    }

    public function show(Driver $driver)
    {
        $driver->load(['branch', 'vehicle', 'driverDocuments', 'driverSchedules']);

        if (request()->ajax()) {
            return response()->json(['driver' => $driver]);
        }

        return view('drivers.show', compact('driver'));
    }

    public function edit(Driver $driver)
    {
        $branches = Branch::where('is_active', true)->get();
        $vehicles = Vehicle::where('is_active', true)->get();
        return view('drivers.edit', compact('driver', 'branches', 'vehicles'));
    }

    public function update(UpdateDriverRequest $request, Driver $driver)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', $driver->is_active);

        $driver->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Driver updated successfully', 'driver' => $driver]);
        }

        return redirect()->route('drivers.index')->with('success', 'Driver updated successfully');
    }

    public function destroy(Driver $driver)
    {
        $driver->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Driver deleted successfully']);
        }

        return redirect()->route('drivers.index')->with('success', 'Driver deleted successfully');
    }
}
