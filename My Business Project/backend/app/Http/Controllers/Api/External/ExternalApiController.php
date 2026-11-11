<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Api\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Public API for external clients.
 *
 * Every route is authenticated with a business scoped API token and the
 * ability required by the operation is enforced by the middleware. The
 * business context always comes from the token.
 */
class ExternalApiController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function products(Request $request): JsonResponse
    {
        $business = app('current.business');

        $query = Product::withoutGlobalScopes()
            ->where('business_id', $business->id);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->orderByDesc('id')
            ->paginate(min(100, (int) $request->query('per_page', 15)));

        return response()->json($products);
    }

    public function customers(Request $request): JsonResponse
    {
        $business = app('current.business');

        $query = Customer::withoutGlobalScopes()
            ->where('business_id', $business->id);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderByDesc('id')
            ->paginate(min(100, (int) $request->query('per_page', 15)));

        return response()->json($customers);
    }

    public function orders(Request $request): JsonResponse
    {
        $business = app('current.business');

        $orders = Order::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->with('items')
            ->orderByDesc('id')
            ->paginate(min(100, (int) $request->query('per_page', 15)));

        return response()->json($orders);
    }

    public function storeOrder(Request $request): JsonResponse
    {
        $business = app('current.business');

        $validated = $request->validate([
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->where('business_id', $business->id),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_status' => ['nullable', Rule::in(['unpaid', 'paid', 'refunded'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = $this->orders->create($validated['items'], [
            'customer_id' => $validated['customer_id'] ?? null,
            'payment_status' => $validated['payment_status'] ?? 'unpaid',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['data' => $order], 201);
    }
}
