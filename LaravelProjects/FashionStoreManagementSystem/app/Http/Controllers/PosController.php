<?php

namespace App\Http\Controllers;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Http\Requests\PosCheckoutRequest;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(private readonly SaleService $sales)
    {
    }

    public function index(): View
    {
        $products = Product::query()
            ->with('variants', 'category', 'images')
            ->active()
            ->whereHas('variants', fn ($q) => $q->where('stock_quantity', '>', 0))
            ->orderBy('name')
            ->paginate(12);

        $categories = Category::query()->active()->orderBy('name')->get();
        $customers = Customer::query()->orderBy('name')->get();

        return view('pos.index', compact('products', 'categories', 'customers'));
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $query = Product::query()
            ->with(['variants' => fn ($q) => $q->orderBy('size'), 'category', 'images'])
            ->active()
            ->whereHas('variants', fn ($q) => $q->where('stock_quantity', '>', 0));

        $q = trim((string) $request->input('q'));

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('brand', 'like', "%{$q}%")
                    ->orWhereHas('variants', function ($variantQuery) use ($q) {
                        $variantQuery->where('sku_variant', 'like', "%{$q}%")
                            ->orWhere('barcode', $q);
                    });
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }

        $products = $query->orderBy('name')->limit(24)->get();

        $html = view('pos.partials.product-grid', ['products' => $products])->render();

        return response()->json(['html' => $html, 'count' => $products->count()]);
    }

    public function variant(ProductVariant $variant): JsonResponse
    {
        $variant->load('product');

        return response()->json([
            'variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'label' => $variant->label ?: 'Default',
            'sku' => $variant->sku_variant,
            'price' => (float) $variant->price,
            'stock' => $variant->stock_quantity,
            'image' => $variant->product->primaryImage()?->url,
        ]);
    }

    public function checkout(PosCheckoutRequest $request): JsonResponse
    {
        try {
            $order = $this->sales->checkout(
                cartItems: $request->input('items'),
                customerId: $request->integer('customer_id') ?: null,
                paymentMethod: PaymentMethod::from($request->input('payment_method')),
                discountType: DiscountType::from($request->input('discount_type')),
                discountValue: (float) $request->input('discount_value', 0),
                amountTendered: $request->filled('amount_tendered') ? (float) $request->input('amount_tendered') : null,
                notes: $request->input('notes'),
                cashier: $request->user(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully!',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total' => (float) $order->total,
                'receipt_url' => route('pos.receipt', $order->id),
            ]);
        } catch (\App\Exceptions\InsufficientStockException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function receipt(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load('items', 'customer', 'user');

        return view('pos.receipt', compact('order'));
    }
}
