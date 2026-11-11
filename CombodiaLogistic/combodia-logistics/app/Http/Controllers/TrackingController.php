<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\TrackingHistory;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        $query = TrackingHistory::with(['shipment', 'dispatch']);

        if ($request->has('shipment_id') && $request->shipment_id !== null) {
            $query->where('shipment_id', $request->shipment_id);
        }

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where('location_name', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%");
        }

        $trackings = $query->latest('recorded_at')->paginate(15)->withQueryString();
        $shipments = Shipment::whereNotIn('status', ['delivered', 'cancelled'])->get();

        if ($request->ajax()) {
            return response()->json(['trackings' => $trackings, 'shipments' => $shipments]);
        }

        return view('tracking.index', compact('trackings', 'shipments'));
    }

    public function show(Shipment $shipment)
    {
        $trackingHistories = TrackingHistory::where('shipment_id', $shipment->id)
            ->orderBy('recorded_at', 'desc')
            ->get();

        if (request()->ajax()) {
            return response()->json(['shipment' => $shipment, 'tracking' => $trackingHistories]);
        }

        return view('tracking.show', compact('shipment', 'trackingHistories'));
    }

    public function updateLocation(Request $request)
    {
        $validated = $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
            'dispatch_id' => 'nullable|exists:dispatches,id',
            'status' => 'required|string|max:255',
            'location_name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0',
            'bearing' => 'nullable|numeric|between:0,360',
            'description' => 'nullable|string|max:1000',
        ]);

        $validated['recorded_at'] = now();
        $validated['source'] = 'gps_simulation';

        $tracking = TrackingHistory::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Location updated successfully', 'tracking' => $tracking]);
        }

        return back()->with('success', 'Location updated successfully');
    }
}
