<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index()
    {
        $categories = Category::where('status', 'active')->pluck('name', 'id');
        return view('pos.index', compact('categories'));
    }

    public function products(Request $request)
    {
        $query = Product::where('status', 'active');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%");
            });
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('barcode')) {
            $query->where('barcode', $request->barcode);
        }

        return $query->limit(50)->get(['id', 'name', 'sku', 'selling_price as price', 'current_stock as stock', 'image']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request) {
            $subtotal = 0;
            $items = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['id']);
                $lineTotal = $product->selling_price * $item['qty'];
                $subtotal += $lineTotal;
                $items[] = [
                    'product' => $product,
                    'qty' => $item['qty'],
                    'price' => $product->selling_price,
                    'total' => $lineTotal,
                ];

                if ($product->current_stock < $item['qty']) {
                    abort(422, "Insufficient stock for {$product->name}");
                }
            }

            $taxAmount = $subtotal * 0.10;
            $grandTotal = $subtotal + $taxAmount;

            $sale = Sale::create([
                'invoice_no' => 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                'customer_id' => $request->customer_id,
                'sale_date' => now(),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => 0,
                'grand_total' => $grandTotal,
                'payment_method' => $request->payment_method,
                'amount_paid' => $request->amount_paid,
                'change_amount' => max(0, $request->amount_paid - $grandTotal),
                'status' => 'completed',
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $product = $item['product'];
                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $item['qty'],
                    'unit_price' => $item['price'],
                    'subtotal' => $item['total'],
                ]);

                $product->decrement('current_stock', $item['qty']);

                $stock = Stock::where('product_id', $product->id)->first();
                if ($stock) {
                    $stock->decrement('quantity', $item['qty']);
                    StockMovement::create([
                        'product_id' => $product->id,
                        'stock_id' => $stock->id,
                        'reference_type' => 'sale',
                        'reference_id' => $sale->id,
                        'type' => 'out',
                        'quantity' => $item['qty'],
                        'unit_price' => $item['price'],
                        'total_price' => $item['total'],
                        'before_quantity' => $stock->quantity + $item['qty'],
                        'after_quantity' => $stock->quantity,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            return response()->json(['sale_id' => $sale->id, 'message' => 'Sale completed successfully']);
        });
    }
}
