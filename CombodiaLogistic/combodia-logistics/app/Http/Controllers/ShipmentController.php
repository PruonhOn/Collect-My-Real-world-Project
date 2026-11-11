<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShipmentRequest;
use App\Http\Requests\UpdateShipmentRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Shipment;
use App\Models\ShipmentStatusHistory;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Shipment::with(['customer', 'branch', 'warehouse', 'user']);

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                    ->orWhere('sender_name', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && $request->type !== null) {
            $query->where('type', $request->type);
        }

        if ($request->has('priority') && $request->priority !== null) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('branch_id') && $request->branch_id !== null) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('date_from') && $request->date_from !== null) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to !== null) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $shipments = $query->latest()->paginate(15)->withQueryString();
        $branches = Branch::where('is_active', true)->get();

        if ($request->ajax()) {
            return response()->json(['shipments' => $shipments, 'branches' => $branches]);
        }

        return view('shipments.index', compact('shipments', 'branches'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('shipments.create', compact('customers', 'branches', 'warehouses'));
    }

    public function store(StoreShipmentRequest $request)
    {
        $validated = $request->validated();
        $validated['shipment_number'] = 'SHP-' . strtoupper(Str::random(10));
        $validated['user_id'] = auth()->id();
        $validated['status'] = 'pending';

        $shipment = Shipment::create($validated);

        ShipmentStatusHistory::create([
            'shipment_id' => $shipment->id,
            'status' => 'pending',
            'notes' => 'Shipment created',
            'changed_by' => auth()->id(),
        ]);

        if ($request->ajax()) {
            return response()->json(['message' => 'Shipment created successfully', 'shipment' => $shipment], 201);
        }

        return redirect()->route('shipments.index')->with('success', 'Shipment created successfully');
    }

    public function show(Shipment $shipment)
    {
        $shipment->load([
            'customer',
            'branch',
            'warehouse',
            'user',
            'shipmentItems',
            'packages',
            'trackingHistories',
            'shipmentStatusHistories',
            'dispatches',
            'invoices',
        ]);

        if (request()->ajax()) {
            return response()->json(['shipment' => $shipment]);
        }

        return view('shipments.show', compact('shipment'));
    }

    public function edit(Shipment $shipment)
    {
        $customers = Customer::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('shipments.edit', compact('shipment', 'customers', 'branches', 'warehouses'));
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment)
    {
        $validated = $request->validated();
        $shipment->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Shipment updated successfully', 'shipment' => $shipment]);
        }

        return redirect()->route('shipments.index')->with('success', 'Shipment updated successfully');
    }

    public function destroy(Shipment $shipment)
    {
        $shipment->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Shipment deleted successfully']);
        }

        return redirect()->route('shipments.index')->with('success', 'Shipment deleted successfully');
    }

    public function approve(Shipment $shipment)
    {
        $shipment->update(['status' => 'approved']);

        ShipmentStatusHistory::create([
            'shipment_id' => $shipment->id,
            'status' => 'approved',
            'notes' => 'Shipment approved',
            'changed_by' => auth()->id(),
        ]);

        if (request()->ajax()) {
            return response()->json(['message' => 'Shipment approved successfully', 'shipment' => $shipment]);
        }

        return back()->with('success', 'Shipment approved successfully');
    }

    public function cancel(Shipment $shipment)
    {
        $request = request();
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $shipment->update(['status' => 'cancelled']);

        ShipmentStatusHistory::create([
            'shipment_id' => $shipment->id,
            'status' => 'cancelled',
            'notes' => $validated['reason'],
            'changed_by' => auth()->id(),
        ]);

        if ($request->ajax()) {
            return response()->json(['message' => 'Shipment cancelled successfully', 'shipment' => $shipment]);
        }

        return back()->with('success', 'Shipment cancelled successfully');
    }

    public function track(Shipment $shipment)
    {
        $shipment->load(['trackingHistories', 'packages']);

        if (request()->ajax()) {
            return response()->json(['shipment' => $shipment, 'tracking' => $shipment->trackingHistories]);
        }

        return view('shipments.track', compact('shipment'));
    }

    public function updateStatus(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,approved,picked_up,in_transit,out_for_delivery,delivered,cancelled',
            'notes' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
        ]);

        $shipment->update(['status' => $validated['status']]);

        ShipmentStatusHistory::create([
            'shipment_id' => $shipment->id,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'changed_by' => auth()->id(),
        ]);

        if ($request->ajax()) {
            return response()->json(['message' => 'Status updated successfully', 'shipment' => $shipment]);
        }

        return back()->with('success', 'Status updated successfully');
    }

    public function generateBarcode(Shipment $shipment)
    {
        $packages = $shipment->packages;

        foreach ($packages as $package) {
            if (empty($package->barcode)) {
                $package->update(['barcode' => 'PKG-' . strtoupper(Str::random(12))]);
            }
        }

        if (request()->ajax()) {
            return response()->json(['message' => 'Barcodes generated successfully', 'packages' => $packages->fresh()]);
        }

        return back()->with('success', 'Barcodes generated successfully');
    }
}
