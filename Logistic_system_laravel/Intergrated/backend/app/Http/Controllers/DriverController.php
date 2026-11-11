<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Vehicle;
use App\Services\DispatchService;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function __construct(protected DispatchService $dispatchService) {}

    public function index(Request $request)
    {
        $query = Driver::with(['vehicle'])
            ->withCount(['shipments', 'activeShipments']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $drivers  = $query->paginate(20)->withQueryString();
        $statuses = [
            Driver::STATUS_AVAILABLE => 'Available',
            Driver::STATUS_ON_ROUTE  => 'On Route',
            Driver::STATUS_OFF_DUTY  => 'Off Duty',
            Driver::STATUS_INACTIVE  => 'Inactive',
        ];

        return view('drivers.index', compact('drivers', 'statuses'));
    }

    public function create()
    {
        $vehicles = Vehicle::whereDoesntHave('driver')->get();
        return view('drivers.create', compact('vehicles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'phone'           => 'required|string|max:20|unique:drivers',
            'email'           => 'nullable|email|unique:drivers',
            'employee_id'     => 'required|string|unique:drivers',
            'license_number'  => 'required|string|unique:drivers',
            'license_expiry'  => 'required|date|after:today',
            'id_number'       => 'required|string|unique:drivers',
            'vehicle_id'      => 'nullable|exists:vehicles,id',
            'notes'           => 'nullable|string',
        ]);

        $driver = Driver::create($data);

        return redirect()
            ->route('drivers.show', $driver)
            ->with('success', "Driver {$driver->name} created.");
    }

    public function show(Driver $driver)
    {
        $driver->load(['vehicle', 'shipments' => fn($q) => $q->latest()->limit(10)]);
        return view('drivers.show', compact('driver'));
    }

    public function edit(Driver $driver)
    {
        $vehicles = Vehicle::whereDoesntHave('driver')
            ->orWhere('id', $driver->vehicle_id)->get();
        return view('drivers.edit', compact('driver', 'vehicles'));
    }

    public function update(Request $request, Driver $driver)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'phone'          => 'required|string|max:20|unique:drivers,phone,' . $driver->id,
            'email'          => 'nullable|email|unique:drivers,email,' . $driver->id,
            'license_expiry' => 'required|date',
            'status'         => 'required|in:available,on_route,off_duty,inactive',
            'vehicle_id'     => 'nullable|exists:vehicles,id',
            'notes'          => 'nullable|string',
        ]);

        $driver->update($data);

        return redirect()->route('drivers.show', $driver)->with('success', 'Driver updated.');
    }

    public function assign(Request $request, Driver $driver)
    {
        $request->validate(['shipment_id' => 'required|exists:shipments,id']);
        $this->dispatchService->assignDriver($driver, $request->shipment_id);
        return back()->with('success', 'Shipment assigned to driver.');
    }

    public function destroy(Driver $driver)
    {
        $driver->delete();
        return redirect()->route('drivers.index')->with('success', 'Driver removed.');
    }
}