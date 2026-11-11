<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReturnRequestRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Returns;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = Returns::with(['shipment', 'customer', 'branch', 'receivedBy']);

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->has('branch_id') && $request->branch_id !== null) {
            $query->where('branch_id', $request->branch_id);
        }

        $returns = $query->latest()->paginate(15)->withQueryString();
        $branches = Branch::where('is_active', true)->get();

        if ($request->ajax()) {
            return response()->json(['returns' => $returns, 'branches' => $branches]);
        }

        return view('returns.index', compact('returns', 'branches'));
    }

    public function create()
    {
        $shipments = Shipment::latest()->get();
        $customers = Customer::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        return view('returns.create', compact('shipments', 'customers', 'branches'));
    }

    public function store(StoreReturnRequestRequest $request)
    {
        $validated = $request->validated();
        $validated['return_number'] = 'RET-' . strtoupper(Str::random(10));
        $validated['received_by'] = auth()->id();
        $validated['status'] = 'pending';
        $validated['total_items'] = $validated['total_items'] ?? 0;
        $validated['total_refund_amount'] = $validated['total_refund_amount'] ?? 0;

        $return = Returns::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Return request created successfully', 'return' => $return], 201);
        }

        return redirect()->route('returns.index')->with('success', 'Return request created successfully');
    }

    public function show(Returns $return)
    {
        $return->load(['shipment', 'customer', 'branch', 'receivedBy', 'returnItems']);

        if (request()->ajax()) {
            return response()->json(['return' => $return]);
        }

        return view('returns.show', compact('return'));
    }

    public function edit(Returns $return)
    {
        $shipments = Shipment::latest()->get();
        $customers = Customer::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        return view('returns.edit', compact('return', 'shipments', 'customers', 'branches'));
    }

    public function update(Request $request, Returns $return)
    {
        $validated = $request->validate([
            'shipment_id' => 'nullable|exists:shipments,id',
            'customer_id' => 'required|exists:customers,id',
            'branch_id' => 'nullable|exists:branches,id',
            'type' => 'required|string|max:255',
            'status' => 'required|string|in:pending,approved,rejected,completed',
            'reason' => 'required|string|max:1000',
            'description' => 'nullable|string|max:1000',
            'total_items' => 'nullable|integer|min:0',
            'total_refund_amount' => 'nullable|numeric|min:0',
        ]);

        $return->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Return updated successfully', 'return' => $return]);
        }

        return redirect()->route('returns.index')->with('success', 'Return updated successfully');
    }

    public function destroy(Returns $return)
    {
        $return->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Return deleted successfully']);
        }

        return redirect()->route('returns.index')->with('success', 'Return deleted successfully');
    }
}
