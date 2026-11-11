<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\PayOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\NotificationService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly NotificationService $notifications,
    ) {}

    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orders->checkout($request->user(), $request->validated('book_ids'));
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), $e->errors(), 422);
        }

        return $this->success(new OrderResource($order->load('items')), 'Order created.', 201);
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()->orders()
            ->with('items')
            ->latest('id')
            ->paginate($request->integer('per_page', 10));

        return $this->success([
            'orders' => OrderResource::collection($orders),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ], 'Orders retrieved.');
    }

    public function show(string $orderNumber, Request $request): JsonResponse
    {
        $order = $request->user()->orders()
            ->with('items')
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order) {
            return $this->error('Order not found.', null, 404);
        }

        return $this->success(new OrderResource($order), 'Order retrieved.');
    }

    public function pay(string $orderNumber, PayOrderRequest $request): JsonResponse
    {
        $order = $request->user()->orders()->where('order_number', $orderNumber)->first();

        if (! $order) {
            return $this->error('Order not found.', null, 404);
        }

        try {
            $wasPaid = $order->isPaid();
            $order = $this->orders->pay($order, $request->input('payment_method', 'mock'));
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), $e->errors(), 422);
        }

        if (! $wasPaid && $order->isPaid()) {
            $this->notifications->orderPaid($order->load('items'));
        }

        return $this->success(new OrderResource($order->load('items')), 'Payment successful.');
    }
}
