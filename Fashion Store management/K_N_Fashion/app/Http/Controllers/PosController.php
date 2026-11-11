<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosController extends Controller
{
    public function index()
    {
        $products = Product::with('variants', 'category')
            ->where('is_active', true)
            ->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $cart = session()->get('pos_cart', []);

        return view('pos.index', compact('products', 'categories', 'customers', 'cart'));
    }

    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::with('variants')->findOrFail($validated['product_id']);
        $variant = null;
        $price = $product->selling_price;

        if ($validated['product_variant_id']) {
            $variant = ProductVariant::findOrFail($validated['product_variant_id']);
            $price += $variant->price_adjustment;
        }

        $cart = session()->get('pos_cart', []);

        $cartKey = $validated['product_id'] . '-' . ($validated['product_variant_id'] ?? '0');

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $validated['quantity'];
        } else {
            $cart[$cartKey] = [
                'product_id' => $product->id,
                'product_variant_id' => $validated['product_variant_id'],
                'name' => $product->name,
                'variant_name' => $variant ? ($variant->size . ' / ' . $variant->color) : null,
                'quantity' => $validated['quantity'],
                'unit_price' => $price,
            ];
        }

        session()->put('pos_cart', $cart);

        return redirect()->route('pos.index')
            ->with('success', 'Item added to cart.');
    }

    public function removeFromCart($key)
    {
        $cart = session()->get('pos_cart', []);

        if (isset($cart[$key])) {
            unset($cart[$key]);
            session()->put('pos_cart', $cart);
        }

        return redirect()->route('pos.index')
            ->with('success', 'Item removed from cart.');
    }

    public function updateCart(Request $request, $key)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = session()->get('pos_cart', []);

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] = $validated['quantity'];
            session()->put('pos_cart', $cart);
        }

        return redirect()->route('pos.index')
            ->with('success', 'Cart updated.');
    }

    public function applyDiscount(Request $request)
    {
        $validated = $request->validate([
            'discount' => 'required|numeric|min:0',
        ]);

        session()->put('pos_discount', $validated['discount']);

        return redirect()->route('pos.index')
            ->with('success', 'Discount applied.');
    }

    public function checkout(Request $request)
    {
        $cart = session()->get('pos_cart', []);
        $discount = session()->get('pos_discount', 0);

        if (empty($cart)) {
            return redirect()->route('pos.index')
                ->with('error', 'Cart is empty.');
        }

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'payment_method' => 'required|string|in:cash,card,bank_transfer,other',
            'payment_status' => 'required|string|in:pending,paid,partial',
            'notes' => 'nullable|string',
        ]);

        $invoiceNo = 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

        $sale = DB::transaction(function () use ($cart, $discount, $validated, $invoiceNo) {
            $total = 0;
            $items = [];

            foreach ($cart as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $total += $subtotal;
                $items[] = new SaleItem([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);
            }

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

            foreach ($cart as $item) {
                if ($item['product_variant_id']) {
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
                            'notes' => "POS Sale #{$sale->invoice_no}",
                            'user_id' => auth()->id(),
                        ]);
                    }
                }
            }

            return $sale;
        });

        session()->forget(['pos_cart', 'pos_discount']);

        return redirect()->route('sales.show', $sale)
            ->with('success', 'Sale completed successfully.');
    }
}
