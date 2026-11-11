<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\OrderRefundRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly SaleService $sales)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::query()
            ->with('customer', 'user', 'items')
            ->when($request->filled('q'), fn ($q) => $q->where('order_number', 'like', '%'.trim($request->input('q')).'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('payment_method'), fn ($q) => $q->where('payment_method', $request->input('payment_method')))
            ->when($request->filled('cashier_id'), fn ($q) => $q->where('user_id', $request->integer('cashier_id')))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('date_to')));

        if (! $request->user()->isAdmin() && ! $request->user()->isManager()) {
            $query->where('user_id', $request->user()->id);
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        $cashiers = User::query()->where('is_active', true)->orderBy('name')->get();
        $customers = Customer::query()->orderBy('name')->get();
        $statuses = OrderStatus::cases();

        return view('orders.index', compact('orders', 'cashiers', 'customers', 'statuses'));
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load('items.variant.product', 'customer', 'user', 'refunds.items');

        $refundableItems = $order->items->filter(fn ($item) => $item->remaining_quantity > 0);

        return view('orders.show', compact('order', 'refundableItems'));
    }

    public function refund(Order $order, OrderRefundRequest $request): RedirectResponse
    {
        $this->authorize('refund', $order);

        try {
            $refund = $this->sales->refund(
                $order,
                $request->input('items'),
                $request->input('reason'),
                $request->user()
            );

            return redirect()
                ->route('orders.show', $order)
                ->with('success', "Refund of {$refund->amount} processed and stock restored.");
        } catch (\DomainException $e) {
            return back()->withErrors(['refund' => $e->getMessage()]);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['refund' => $e->getMessage()]);
        }
    }
}
