<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePickupRequestRequest;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\PickupRequest;
use App\Models\Shipment;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PickupRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = PickupRequest::with(['customer', 'shipment', 'driver', 'vehicle']);

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('pickup_address', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        $pickupRequests = $query->latest()->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return response()->json(['pickupRequests' => $pickupRequests]);
        }

        return view('pickup-requests.index', compact('pickupRequests'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)->get();
        $shipments = Shipment::latest()->get();
        return view('pickup-requests.create', compact('customers', 'shipments'));
    }

    public function store(StorePickupRequestRequest $request)
    {
        $validated = $request->validated();
        $validated['request_number'] = 'PCK-' . strtoupper(Str::random(10));
        $validated['user_id'] = auth()->id();
        $validated['status'] = 'pending';

        $pickupRequest = PickupRequest::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Pickup request created successfully', 'pickupRequest' => $pickupRequest], 201);
        }

        return redirect()->route('pickup-requests.index')->with('success', 'Pickup request created successfully');
    }

    public function show(PickupRequest $pickupRequest)
    {
        $pickupRequest->load(['customer', 'shipment', 'driver', 'vehicle']);

        if (request()->ajax()) {
            return response()->json(['pickupRequest' => $pickupRequest]);
        }

        return view('pickup-requests.show', compact('pickupRequest'));
    }

    public function edit(PickupRequest $pickupRequest)
    {
        $customers = Customer::where('is_active', true)->get();
        $shipments = Shipment::latest()->get();
        return view('pickup-requests.edit', compact('pickupRequest', 'customers', 'shipments'));
    }

    public function update(Request $request, PickupRequest $pickupRequest)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'shipment_id' => 'nullable|exists:shipments,id',
            'pickup_address' => 'required|string|max:500',
            'pickup_city' => 'required|string|max:255',
            'pickup_latitude' => 'nullable|numeric|between:-90,90',
            'pickup_longitude' => 'nullable|numeric|between:-180,180',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:20',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:1000',
        ]);

        $pickupRequest->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Pickup request updated successfully', 'pickupRequest' => $pickupRequest]);
        }

        return redirect()->route('pickup-requests.index')->with('success', 'Pickup request updated successfully');
    }

    public function destroy(PickupRequest $pickupRequest)
    {
        $pickupRequest->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Pickup request deleted successfully']);
        }

        return redirect()->route('pickup-requests.index')->with('success', 'Pickup request deleted successfully');
    }

    public function assign(Request $request, PickupRequest $pickupRequest)
    {
        $validated = $request->validate([
            'assigned_driver_id' => 'required|exists:drivers,id',
            'assigned_vehicle_id' => 'required|exists:vehicles,id',
        ]);

        $pickupRequest->update([
            'assigned_driver_id' => $validated['assigned_driver_id'],
            'assigned_vehicle_id' => $validated['assigned_vehicle_id'],
            'status' => 'assigned',
        ]);

        if ($request->ajax()) {
            return response()->json(['message' => 'Driver and vehicle assigned successfully', 'pickupRequest' => $pickupRequest]);
        }

        return back()->with('success', 'Driver and vehicle assigned successfully');
    }

    public function confirm(PickupRequest $pickupRequest)
    {
        $pickupRequest->update(['status' => 'confirmed']);

        if (request()->ajax()) {
            return response()->json(['message' => 'Pickup request confirmed successfully', 'pickupRequest' => $pickupRequest]);
        }

        return back()->with('success', 'Pickup request confirmed successfully');
    }
}
