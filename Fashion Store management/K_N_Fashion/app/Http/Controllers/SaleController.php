<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Customer;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with('customer', 'user');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sales = $query->latest()->paginate(15);

        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::with('variants')->where('is_active', true)->get();
        return view('sales.create', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'payment_method' => 'required|string|in:cash,card,bank_transfer,other',
            'payment_status' => 'required|string|in:pending,paid,partial',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
        ]);

        $invoiceNo = 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

        $sale = DB::transaction(function () use ($validated, $invoiceNo, $request) {
            $total = 0;
            $items = [];

            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $total += $subtotal;
                $items[] = new SaleItem([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);
            }

            $discount = $validated['discount'] ?? 0;
            $grandTotal = $total - $discount;

            $sale = Sale::create([
                'invoice_no' => $invoiceNo,
                'customer_id' => $validated['customer_id'],
                'user_id' => auth()->id(),
                'subtotal' => $total,
                'discount' => $discount,
                'tax' => 0,
                'grand_total' => $grandTotal,
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $sale->items()->saveMany($items);

            foreach ($validated['items'] as $item) {
                $variant = ProductVariant::find($item['product_variant_id']);
                if ($variant) {
                    $variant->decrement('quantity', $item['quantity']);

                    InventoryLog::create([
                        'product_id' => $item['product_id'],
                        'product_variant_id' => $variant->id,
                        'quantity_change' => -$item['quantity'],
                        'type' => 'out',
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                        'notes' => "Sale #{$sale->invoice_no}",
                        'user_id' => auth()->id(),
                    ]);
                }
            }

            return $sale;
        });

        return redirect()->route('sales.show', $sale)
            ->with('success', 'Sale created successfully.');
    }

    public function show(Sale $sale)
    {
        $sale->load('customer', 'user', 'items.product', 'items.variant');
        return view('sales.show', compact('sale'));
    }

    public function invoice(Sale $sale)
    {
        $sale->load('customer', 'user', 'items.product', 'items.variant');

        return view('sales.invoice', compact('sale'));
    }

    public function destroy(Sale $sale)
    {
        DB::transaction(function () use ($sale) {
            foreach ($sale->items as $item) {
                if ($item->product_variant_id) {
                    ProductVariant::where('id', $item->product_variant_id)
                        ->increment('quantity', $item->quantity);
                }
            }
            $sale->items()->delete();
            $sale->delete();
        });

        return redirect()->route('sales.index')
            ->with('success', 'Sale deleted successfully.');
    }
}
