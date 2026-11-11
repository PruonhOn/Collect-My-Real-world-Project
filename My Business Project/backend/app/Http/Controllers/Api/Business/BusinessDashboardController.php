<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Api\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\Features\FeatureGate;
use Illuminate\Http\JsonResponse;

class BusinessDashboardController extends Controller
{
    public function __construct(protected FeatureGate $gate) {}

    public function index(): JsonResponse
    {
        $business = app('current.business');
        $tz = $business->timezone ?: config('app.timezone', 'UTC');

        $today = now()->setTimezone($tz)->startOfDay();
        $monthStart = now()->setTimezone($tz)->startOfMonth();

        $todaySales = Order::where('status', '!=', 'canceled')
            ->whereBetween('created_at', [$today, now()])
            ->sum('total');

        $monthSales = Order::where('status', '!=', 'canceled')
            ->where('created_at', '>=', $monthStart)
            ->sum('total');

        $orderCount = Order::where('created_at', '>=', $monthStart)->count();
        $customerCount = \App\Models\Customer::count();
        $productCount = Product::count();

        $recentOrders = Order::with('customer')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'customer' => $o->customer?->first_name,
                'total' => $o->total,
                'currency' => $o->currency,
                'status' => $o->status,
                'created_at' => $o->created_at->toIso8601String(),
            ]);

        $usage = [];
        foreach (['max_products', 'max_customers', 'max_members', 'max_orders_monthly'] as $key) {
            $limit = $this->gate->limit($business, $key);
            $used = $this->gate->usage($business, $key);
            $usage[] = [
                'key' => $key,
                'used' => $used,
                'limit' => $limit,
                'remaining' => $limit === null ? null : max(0, $limit - $used),
                'percentage' => $limit ? min(100, (int) round(($used / $limit) * 100)) : 0,
            ];
        }

        return response()->json([
            'data' => [
                'today_sales' => (int) $todaySales,
                'month_sales' => (int) $monthSales,
                'order_count' => (int) $orderCount,
                'customer_count' => (int) $customerCount,
                'product_count' => (int) $productCount,
                'recent_orders' => $recentOrders,
                'usage' => $usage,
                'currency' => $business->currency,
            ],
        ]);
    }
}
