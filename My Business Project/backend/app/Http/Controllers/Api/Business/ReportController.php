<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Api\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function sales(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);

        $business = app('current.business');

        $totals = Order::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('status', '!=', 'canceled')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) as orders_count, COALESCE(SUM(total),0) as sales_total, COALESCE(SUM(discount),0) as discount_total, COALESCE(SUM(tax),0) as tax_total')
            ->first();

        $daily = Order::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('status', '!=', 'canceled')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as orders_count, SUM(total) as sales_total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->day,
                'orders' => (int) $row->orders_count,
                'sales' => (int) $row->sales_total,
            ]);

        $byStatus = Order::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as orders_count, SUM(total) as sales_total')
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'orders' => (int) $row->orders_count,
                'sales' => (int) $row->sales_total,
            ]);

        return response()->json([
            'data' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'totals' => [
                    'orders' => (int) $totals->orders_count,
                    'sales' => (int) $totals->sales_total,
                    'discount' => (int) $totals->discount_total,
                    'tax' => (int) $totals->tax_total,
                ],
                'daily' => $daily,
                'by_status' => $byStatus,
                'currency' => $business->currency,
            ],
        ]);
    }

    public function profit(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);
        $business = app('current.business');

        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.business_id', $business->id)
            ->where('orders.status', '!=', 'canceled')
            ->whereBetween('orders.created_at', [$from, $to])
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->selectRaw('order_items.name as name, SUM(order_items.quantity) as qty, SUM(order_items.total) as revenue')
            ->selectRaw('COALESCE(SUM((order_items.unit_price - products.cost_price) * order_items.quantity), 0) as profit')
            ->groupBy('order_items.name')
            ->orderByDesc('revenue')
            ->get();

        $hasCostData = DB::table('products')
            ->where('business_id', $business->id)
            ->whereNotNull('cost_price')
            ->exists();

        $bestSellers = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.business_id', $business->id)
            ->where('orders.status', '!=', 'canceled')
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('order_items.name as name, SUM(order_items.quantity) as qty, SUM(order_items.total) as revenue')
            ->groupBy('order_items.name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'quantity' => (int) $row->qty,
                'revenue' => (int) $row->revenue,
            ]);

        return response()->json([
            'data' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'profit_available' => $hasCostData,
                'profit' => $hasCostData ? (int) $rows->sum('profit') : null,
                'revenue' => (int) $rows->sum('revenue'),
                'by_product' => $rows->map(fn ($row) => [
                    'name' => $row->name,
                    'quantity' => (int) $row->qty,
                    'revenue' => (int) $row->revenue,
                    'profit' => $hasCostData ? (int) $row->profit : null,
                ]),
                'best_sellers' => $bestSellers,
                'currency' => $business->currency,
            ],
        ]);
    }

    /**
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}
     */
    protected function range(Request $request): array
    {
        $business = app('current.business');
        $tz = $business->timezone ?: config('app.timezone', 'UTC');

        $from = $request->query('from')
            ? now()->setTimezone($tz)->parse($request->query('from'))->startOfDay()
            : now()->setTimezone($tz)->startOfMonth();

        $to = $request->query('to')
            ? now()->setTimezone($tz)->parse($request->query('to'))->endOfDay()
            : now()->setTimezone($tz)->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}
