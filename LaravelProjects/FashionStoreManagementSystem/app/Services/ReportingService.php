<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function dashboardStats(): array
    {
        $today = Carbon::today();

        $todayRevenue = (float) Order::query()
            ->whereDate('created_at', $today)
            ->whereIn('status', [OrderStatus::Completed->value, OrderStatus::PartiallyRefunded->value])
            ->sum(DB::raw('total - refunded_amount'));

        $todayOrders = Order::query()->whereDate('created_at', $today)->count();
        $todayItems = (int) Order::query()->whereDate('orders.created_at', $today)->join('order_items', 'order_items.order_id', '=', 'orders.id')->sum('order_items.quantity');
        $todayCustomers = (int) Order::query()->whereDate('orders.created_at', $today)->whereNotNull('customer_id')->count();

        $monthRevenue = (float) Order::query()
            ->whereBetween('created_at', [Carbon::today()->startOfMonth(), Carbon::now()])
            ->whereIn('status', [OrderStatus::Completed->value, OrderStatus::PartiallyRefunded->value])
            ->sum(DB::raw('total - refunded_amount'));

        $avgOrder = $todayOrders > 0 ? round($todayRevenue / $todayOrders, 2) : 0;

        return compact('todayRevenue', 'todayOrders', 'todayItems', 'todayCustomers', 'monthRevenue', 'avgOrder');
    }

    public function revenueChartData(int $days = 30): array
    {
        $start = Carbon::today()->subDays($days - 1);

        $rows = Order::query()
            ->where('created_at', '>=', $start)
            ->whereIn('status', [OrderStatus::Completed->value, OrderStatus::PartiallyRefunded->value])
            ->selectRaw('DATE(created_at) as day, SUM(total - refunded_amount) as revenue, COUNT(*) as orders')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('revenue', 'day')
            ->map(fn ($v) => round((float) $v, 2));

        $labels = [];
        $values = [];
        $orderCounts = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M d');
            $values[] = $rows[$key] ?? 0;
            $orderCounts[] = 0;
        }

        return ['labels' => $labels, 'revenue' => $values, 'orders' => $orderCounts];
    }

    public function topProducts(int $limit = 5): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', [OrderStatus::Completed->value, OrderStatus::PartiallyRefunded->value])
            ->select('order_items.product_name')
            ->selectRaw('SUM(order_items.quantity) as total_quantity')
            ->selectRaw('SUM(order_items.line_total) as total_revenue')
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->product_name,
                'quantity' => (int) $row->total_quantity,
                'revenue' => round((float) $row->total_revenue, 2),
            ])
            ->all();
    }

    public function lowStockItems(int $threshold = 5, int $limit = 10)
    {
        return ProductVariant::query()
            ->with('product')
            ->where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<=', $threshold)
            ->orderBy('stock_quantity')
            ->limit($limit)
            ->get();
    }

    public function outOfStockItems(int $limit = 10)
    {
        return ProductVariant::query()
            ->with('product')
            ->where('stock_quantity', '<=', 0)
            ->limit($limit)
            ->get();
    }

    public function salesReport(Carbon $from, Carbon $to, ?int $cashierId = null, ?int $customerId = null): array
    {
        $query = Order::query()
            ->with(['customer', 'user', 'items'])
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()]);

        if ($cashierId) {
            $query->where('user_id', $cashierId);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $orders = $query->orderByDesc('created_at')->get();

        $netTotal = $orders->reduce(
            fn (float $carry, Order $order) => $carry + (float) $order->total - (float) $order->refunded_amount,
            0.0
        );

        $grossTotal = $orders->sum('total');
        $refundedTotal = $orders->sum('refunded_amount');
        $itemCount = $orders->reduce(fn (int $carry, Order $order) => $carry + $order->items->sum('quantity'), 0);

        $daily = Order::query()
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->when($cashierId, fn ($q) => $q->where('user_id', $cashierId))
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->selectRaw('DATE(created_at) as day, SUM(total - refunded_amount) as revenue, COUNT(*) as orders')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return compact('orders', 'netTotal', 'grossTotal', 'refundedTotal', 'itemCount', 'daily');
    }

    public function inventoryValuation(): array
    {
        $summary = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.brand',
            ])
            ->selectRaw('COALESCE(SUM(product_variants.stock_quantity), 0) as total_stock')
            ->selectRaw('ROUND(COALESCE(SUM(product_variants.stock_quantity * products.cost_price), 0), 2) as cost_value')
            ->selectRaw('ROUND(COALESCE(SUM(product_variants.stock_quantity * COALESCE(product_variants.price_override, products.base_price)), 0), 2) as retail_value')
            ->leftJoin('product_variants', 'product_variants.product_id', '=', 'products.id')
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.brand')
            ->orderByDesc('cost_value')
            ->get();

        $totals = [
            'total_stock' => (int) $summary->sum('total_stock'),
            'cost_value' => round((float) $summary->sum('cost_value'), 2),
            'retail_value' => round((float) $summary->sum('retail_value'), 2),
            'potential_profit' => round((float) $summary->sum('retail_value') - (float) $summary->sum('cost_value'), 2),
        ];

        return ['rows' => $summary, 'totals' => $totals];
    }
}
