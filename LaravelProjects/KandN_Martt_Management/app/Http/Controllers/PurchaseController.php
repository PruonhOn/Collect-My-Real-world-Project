<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $query = Purchase::with(['supplier:id,name', 'creator:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $purchases = $query->latest()->paginate(20);
        $suppliers = Supplier::where('status', 'active')->pluck('name', 'id');

        return view('purchases.index', compact('purchases', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Supplier::where('status', 'active')->pluck('name', 'id');
        $products = Product::where('status', 'active')->pluck('name', 'id');
        return view('purchases.form', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100|unique:purchases,reference_no',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'status' => 'required|in:pending,ordered,received,cancelled',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
        ]);

        $validated['created_by'] = auth()->id();

        $purchase = Purchase::create($validated);

        foreach ($request->items as $item) {
            $purchase->purchaseDetails()->create($item);
        }

        if ($validated['status'] === 'received') {
            $this->inventoryService->processPurchaseStock($purchase);
        }

        return redirect()->route('purchases.index')->with('success', 'Purchase created successfully');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'purchaseDetails.product', 'creator:id,name']);
        return view('purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase)
    {
        $suppliers = Supplier::where('status', 'active')->pluck('name', 'id');
        $products = Product::where('status', 'active')->pluck('name', 'id');
        return view('purchases.form', compact('purchase', 'suppliers', 'products'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100|unique:purchases,reference_no,' . $purchase->id,
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'status' => 'required|in:pending,ordered,received,cancelled',
            'notes' => 'nullable|string',
        ]);

        $purchase->update($validated);

        return redirect()->route('purchases.index')->with('success', 'Purchase updated successfully');
    }

    public function destroy(Purchase $purchase)
    {
        $purchase->delete();
        return redirect()->route('purchases.index')->with('success', 'Purchase deleted successfully');
    }
}
