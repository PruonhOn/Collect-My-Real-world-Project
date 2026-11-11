<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDispatchRequest;
use App\Models\Dispatch;
use App\Models\DispatchDriver;
use App\Models\Driver;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DispatchController extends Controller
{
    public function index(Request $request)
    {
        $query = Dispatch::with(['shipment', 'deliveryRoute', 'dispatchDrivers.driver']);

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('dispatch_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        $dispatches = $query->latest()->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return response()->json(['dispatches' => $dispatches]);
        }

        return view('dispatches.index', compact('dispatches'));
    }

    public function create()
    {
        $shipments = Shipment::whereNotIn('status', ['delivered', 'cancelled'])->get();
        return view('dispatches.create', compact('shipments'));
    }

    public function store(StoreDispatchRequest $request)
    {
        $validated = $request->validated();
        $validated['dispatch_number'] = 'DSP-' . strtoupper(Str::random(10));
        $validated['status'] = 'pending';

        $dispatch = Dispatch::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Dispatch created successfully', 'dispatch' => $dispatch], 201);
        }

        return redirect()->route('dispatches.index')->with('success', 'Dispatch created successfully');
    }

    public function show(Dispatch $dispatch)
    {
        $dispatch->load(['shipment', 'deliveryRoute', 'dispatchDrivers.driver', 'trackingHistories']);

        if (request()->ajax()) {
            return response()->json(['dispatch' => $dispatch]);
        }

        return view('dispatches.show', compact('dispatch'));
    }

    public function edit(Dispatch $dispatch)
    {
        $shipments = Shipment::whereNotIn('status', ['delivered', 'cancelled'])->get();
        return view('dispatches.edit', compact('dispatch', 'shipments'));
    }

    public function update(Request $request, Dispatch $dispatch)
    {
        $validated = $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
            'route_id' => 'nullable|exists:routes,id',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:1000',
        ]);

        $dispatch->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Dispatch updated successfully', 'dispatch' => $dispatch]);
        }

        return redirect()->route('dispatches.index')->with('success', 'Dispatch updated successfully');
    }

    public function destroy(Dispatch $dispatch)
    {
        $dispatch->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Dispatch deleted successfully']);
        }

        return redirect()->route('dispatches.index')->with('success', 'Dispatch deleted successfully');
    }

    public function assignDriver(Request $request, Dispatch $dispatch)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
        ]);

        DispatchDriver::updateOrCreate(
            ['dispatch_id' => $dispatch->id, 'driver_id' => $validated['driver_id']],
            ['status' => 'assigned']
        );

        if ($request->ajax()) {
            return response()->json(['message' => 'Driver assigned successfully', 'dispatch' => $dispatch->fresh(['dispatchDrivers.driver'])]);
        }

        return back()->with('success', 'Driver assigned successfully');
    }

    public function load(Dispatch $dispatch)
    {
        $dispatch->update([
            'status' => 'loaded',
            'dispatched_at' => now(),
        ]);

        $dispatch->shipment->update(['status' => 'in_transit']);

        if (request()->ajax()) {
            return response()->json(['message' => 'Dispatch loaded successfully', 'dispatch' => $dispatch]);
        }

        return back()->with('success', 'Dispatch loaded and shipment is now in transit');
    }

    public function complete(Dispatch $dispatch)
    {
        $dispatch->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $dispatch->shipment->update(['status' => 'delivered', 'delivery_date' => now()]);

        $dispatch->dispatchDrivers()->update(['status' => 'completed', 'completed_at' => now()]);

        if (request()->ajax()) {
            return response()->json(['message' => 'Dispatch completed successfully', 'dispatch' => $dispatch]);
        }

        return back()->with('success', 'Dispatch completed successfully');
    }
}
