<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $query = Sale::with(['customer:id,name', 'creator:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        $sales = $query->latest()->paginate(20);
        $customers = Customer::where('status', 'active')->pluck('name', 'id');

        return view('sales.index', compact('sales', 'customers'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->pluck('name', 'id');
        $products = Product::with('unit:id,short_name')->where('status', 'active')->get();
        return view('sales.form', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sale_date' => 'required|date',
            'invoice_no' => 'nullable|string|max:100|unique:sales,invoice_no',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'due_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,bank,mobile',
            'status' => 'required|in:pending,completed,cancelled',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
        ]);

        $validated['created_by'] = auth()->id();

        $sale = Sale::create($validated);

        foreach ($request->items as $item) {
            $sale->saleDetails()->create($item);
        }

        if ($validated['status'] === 'completed') {
            $this->inventoryService->processSaleStock($sale);
        }

        return redirect()->route('sales.index')->with('success', 'Sale created successfully');
    }

    public function show(Sale $sale)
    {
        $sale->load(['customer', 'saleDetails.product', 'creator:id,name']);
        return view('sales.show', compact('sale'));
    }

    public function edit(Sale $sale)
    {
        $customers = Customer::where('status', 'active')->pluck('name', 'id');
        $products = Product::with('unit:id,short_name')->where('status', 'active')->get();
        return view('sales.form', compact('sale', 'customers', 'products'));
    }

    public function update(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sale_date' => 'required|date',
            'invoice_no' => 'nullable|string|max:100|unique:sales,invoice_no,' . $sale->id,
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'due_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,bank,mobile',
            'status' => 'required|in:pending,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $sale->update($validated);

        return redirect()->route('sales.index')->with('success', 'Sale updated successfully');
    }

    public function destroy(Sale $sale)
    {
        $sale->delete();
        return redirect()->route('sales.index')->with('success', 'Sale deleted successfully');
    }
}
