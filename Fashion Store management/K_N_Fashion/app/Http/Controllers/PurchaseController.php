<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with('supplier', 'user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $purchases = $query->latest()->paginate(15);

        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products = Product::with('variants')->where('is_active', true)->get();
        return view('purchases.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'status' => 'required|string|in:pending,ordered,received,cancelled',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $referenceNo = 'PO-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

        $purchase = DB::transaction(function () use ($validated, $referenceNo, $request) {
            $total = 0;
            $items = [];

            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_cost'];
                $total += $subtotal;
                $items[] = new PurchaseItem([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $subtotal,
                ]);
            }

            $purchase = Purchase::create([
                'reference_no' => $referenceNo,
                'supplier_id' => $validated['supplier_id'],
                'user_id' => auth()->id(),
                'total_amount' => $total,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $purchase->items()->saveMany($items);

            if ($validated['status'] === 'received') {
                foreach ($validated['items'] as $item) {
                    $variant = ProductVariant::find($item['product_variant_id']);
                    if ($variant) {
                        $variant->increment('quantity', $item['quantity']);

                        InventoryLog::create([
                            'product_id' => $item['product_id'],
                            'product_variant_id' => $variant->id,
                            'quantity_change' => $item['quantity'],
                            'type' => 'in',
                            'reference_type' => Purchase::class,
                            'reference_id' => $purchase->id,
                            'notes' => "Purchase #{$purchase->reference_no}",
                            'user_id' => auth()->id(),
                        ]);
                    }
                }
            }

            return $purchase;
        });

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'Purchase created successfully.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('supplier', 'user', 'items.product', 'items.variant');
        return view('purchases.show', compact('purchase'));
    }

    public function destroy(Purchase $purchase)
    {
        DB::transaction(function () use ($purchase) {
            if ($purchase->status === 'received') {
                foreach ($purchase->items as $item) {
                    if ($item->product_variant_id) {
                        ProductVariant::where('id', $item->product_variant_id)
                            ->decrement('quantity', $item->quantity);
                    }
                }
            }
            $purchase->items()->delete();
            $purchase->delete();
        });

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase deleted successfully.');
    }
}
