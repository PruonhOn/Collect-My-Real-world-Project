<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Api\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function index(Request $request): JsonResponse
    {
        $query = Order::query()->with('customer', 'items');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $sort = $request->query('sort', 'created_at');
        $direction = $request->query('direction', 'desc');
        if (! in_array($sort, ['created_at', 'total', 'order_number', 'status'], true)) {
            $sort = 'created_at';
        }
        $query->orderBy($sort, $direction === 'asc' ? 'asc' : 'desc');

        return response()->json($query->paginate(min(100, (int) $request->query('per_page', 15))));
    }

    public function store(Request $request): JsonResponse
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
            'discount' => ['nullable', 'integer', 'min:0'],
            'tax' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['pending', 'processing', 'completed', 'canceled', 'refunded'])],
            'payment_status' => ['nullable', Rule::in(['unpaid', 'paid', 'partial', 'refunded'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $order = $this->orders->create($validated['items'], $validated);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage(), 'upgrade_required' => str_contains($e->getMessage(), 'limit')], 422);
        }

        return response()->json(['data' => $order], 201);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorizeSameBusiness($order);

        return response()->json(['data' => $order->load('customer', 'items.product')]);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $this->authorizeSameBusiness($order);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'processing', 'completed', 'canceled', 'refunded'])],
            'payment_status' => ['nullable', Rule::in(['unpaid', 'paid', 'partial', 'refunded'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $order->update($validated);

        return response()->json(['data' => $order->fresh()->load('items')]);
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->authorizeSameBusiness($order);

        $order->delete();

        return response()->json(['message' => 'Order deleted.']);
    }

    protected function authorizeSameBusiness(Order $order): void
    {
        $business = app('current.business');
        if ($order->business_id !== $business->id) {
            abort(404, 'Order not found.');
        }
    }
}
