<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function getDashboardData(?int $branchId = null): array
    {
        return [
            'shipment_stats' => $this->getShipmentStats($branchId),
            'revenue_stats' => $this->getRevenueStats($branchId),
            'expense_stats' => $this->getExpenseStats($branchId),
            'delivery_stats' => $this->getDeliveryStats($branchId),
            'recent_activity' => $this->getRecentActivity(10, $branchId),
            'shipments_by_status' => $this->getShipmentsByStatus($branchId),
            'monthly_revenue' => $this->getMonthlyRevenue(6, $branchId),
            'top_customers' => $this->getTopCustomers(5, $branchId),
        ];
    }

    public function getShipmentStats(?int $branchId = null): array
    {
        $query = Shipment::query();

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId)
                    ->orWhere('destination_branch_id', $branchId);
            });
        }

        return [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'in_transit' => (clone $query)->where('status', 'in_transit')->count(),
            'delivered' => (clone $query)->where('status', 'delivered')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'today' => (clone $query)->whereDate('created_at', today())->count(),
            'this_week' => (clone $query)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => (clone $query)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];
    }

    public function getRevenueStats(?int $branchId = null): array
    {
        $query = Invoice::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return [
            'total_invoiced' => (clone $query)->sum('total'),
            'total_paid' => (clone $query)->sum('paid_amount'),
            'total_outstanding' => (clone $query)->sum('balance'),
            'this_month' => (clone $query)->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total'),
            'overdue' => (clone $query)->where('status', '!=', 'paid')
                ->where('due_date', '<', now())
                ->where('balance', '>', 0)
                ->sum('balance'),
        ];
    }

    public function getExpenseStats(?int $branchId = null): array
    {
        $query = Expense::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return [
            'total' => (clone $query)->sum('amount'),
            'this_month' => (clone $query)->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'pending_approval' => (clone $query)->where('status', 'pending')->count(),
            'approved' => (clone $query)->where('status', 'approved')->sum('amount'),
        ];
    }

    public function getDeliveryStats(?int $branchId = null): array
    {
        $query = Delivery::query();

        if ($branchId) {
            $query->whereHas('shipment', function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId)
                    ->orWhere('destination_branch_id', $branchId);
            });
        }

        return [
            'total' => (clone $query)->count(),
            'assigned' => (clone $query)->where('status', 'assigned')->count(),
            'in_transit' => (clone $query)->where('status', 'in_transit')->count(),
            'delivered' => (clone $query)->where('status', 'delivered')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'today' => (clone $query)->whereDate('scheduled_date', today())->count(),
        ];
    }

    public function getShipmentsByStatus(?int $branchId = null): Collection
    {
        $query = Shipment::query();

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId)
                    ->orWhere('destination_branch_id', $branchId);
            });
        }

        return $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');
    }

    public function getMonthlyRevenue(int $months = 6, ?int $branchId = null): Collection
    {
        $query = Invoice::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->where('created_at', '>=', now()->subMonths($months))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total) as total, SUM(paid_amount) as paid")
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    public function getTopCustomers(int $limit = 5, ?int $branchId = null): Collection
    {
        $query = Invoice::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->selectRaw('customer_id, SUM(total) as total_amount, COUNT(*) as invoice_count')
            ->groupBy('customer_id')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->load('customer');
    }

    public function getRecentActivity(int $limit = 10, ?int $branchId = null): Collection
    {
        $shipments = Shipment::query()
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId)
                    ->orWhere('destination_branch_id', $branchId);
            })
            ->with('customer')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return (object) [
                    'type' => 'shipment',
                    'color' => 'primary',
                    'icon' => 'box-seam',
                    'description' => "Shipment {$item->tracking_number} - {$item->status}",
                    'created_at' => $item->created_at,
                    'user' => null,
                ];
            });

        $invoices = Invoice::query()
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->with('customer')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return (object) [
                    'type' => 'invoice',
                    'color' => 'success',
                    'icon' => 'cash-coin',
                    'description' => "Invoice {$item->invoice_number} - \${$item->total}",
                    'created_at' => $item->created_at,
                    'user' => null,
                ];
            });

        return $shipments->concat($invoices)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();
    }

    public function getBranchPerformance(?int $branchId = null): array
    {
        $query = Shipment::query();

        if ($branchId) {
            $query->where('origin_branch_id', $branchId);
        }

        $totalShipments = (clone $query)->count();
        $deliveredShipments = (clone $query)->where('status', 'delivered')->count();
        $onTimeDeliveries = (clone $query)->where('status', 'delivered')
            ->whereColumn('actual_delivery', '<=', 'estimated_delivery')
            ->count();

        return [
            'total_shipments' => $totalShipments,
            'delivered' => $deliveredShipments,
            'delivery_rate' => $totalShipments > 0 ? round(($deliveredShipments / $totalShipments) * 100, 2) : 0,
            'on_time_rate' => $deliveredShipments > 0 ? round(($onTimeDeliveries / $deliveredShipments) * 100, 2) : 0,
        ];
    }

    public function getStats(?int $branchId = null): array
    {
        $shipmentStats = $this->getShipmentStats($branchId);
        $revenueStats = $this->getRevenueStats($branchId);
        $expenseStats = $this->getExpenseStats($branchId);
        $deliveryStats = $this->getDeliveryStats($branchId);

        $totalDelivered = $shipmentStats['delivered'] ?? 0;
        $totalShipments = $shipmentStats['total'] ?? 1;
        $deliveryRate = $totalShipments > 0 ? round(($totalDelivered / $totalShipments) * 100, 1) : 0;

        $currentMonthShipments = $shipmentStats['this_month'];
        $lastMonthShipments = Shipment::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $shipmentsGrowth = $lastMonthShipments > 0 ? round((($currentMonthShipments - $lastMonthShipments) / $lastMonthShipments) * 100, 1) : 0;

        $currentMonthRevenue = $revenueStats['this_month'];
        $lastMonthRevenue = Invoice::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('total');
        $revenueGrowth = $lastMonthRevenue > 0 ? round((($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1) : 0;

        $currentMonthExpenses = $expenseStats['this_month'];
        $lastMonthExpenses = Expense::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount');
        $expenseChange = $lastMonthExpenses > 0 ? round((($currentMonthExpenses - $lastMonthExpenses) / $lastMonthExpenses) * 100, 1) : 0;

        $warehouseStock = \App\Models\InventoryItem::sum('quantity');
        $warehouseCount = \App\Models\Warehouse::count();
        $activeVehicles = \App\Models\Vehicle::where('status', 'active')->count();
        $totalVehicles = \App\Models\Vehicle::count();

        $pendingCount = $shipmentStats['pending'] ?? 0;
        $failedCount = $deliveryStats['failed'] ?? 0;

        return [
            'total_shipments' => $totalShipments,
            'shipments_growth' => $shipmentsGrowth,
            'active_deliveries' => $deliveryStats['in_transit'] ?? 0,
            'warehouse_stock' => $warehouseStock,
            'warehouse_count' => $warehouseCount,
            'monthly_revenue' => $currentMonthRevenue,
            'revenue_growth' => $revenueGrowth,
            'monthly_expenses' => $currentMonthExpenses,
            'expense_change' => $expenseChange,
            'active_vehicles' => $activeVehicles,
            'total_vehicles' => $totalVehicles,
            'success_rate' => $deliveryRate,
            'pending_rate' => $totalShipments > 0 ? round(($pendingCount / $totalShipments) * 100, 1) : 0,
            'failed_rate' => $totalShipments > 0 ? round(($failedCount / $totalShipments) * 100, 1) : 0,
        ];
    }

    public function getShipmentChartData(?int $branchId = null): array
    {
        $labels = [];
        $data = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M d');

            $query = Shipment::whereDate('created_at', $date);
            if ($branchId) {
                $query->where(function ($q) use ($branchId) {
                    $q->where('origin_branch_id', $branchId)
                        ->orWhere('destination_branch_id', $branchId);
                });
            }
            $data[] = $query->count();
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    public function getRevenueChartData(?int $branchId = null): array
    {
        $monthlyRevenue = $this->getMonthlyRevenue(12, $branchId);

        return [
            'labels' => $monthlyRevenue->pluck('month')->map(function ($m) {
                return \Carbon\Carbon::createFromFormat('Y-m', $m)->format('M Y');
            })->toArray(),
            'data' => $monthlyRevenue->pluck('total')->toArray(),
        ];
    }

    public function getRecentShipments(int $limit = 10, ?int $branchId = null): Collection
    {
        $query = Shipment::with(['customer', 'originBranch', 'destinationBranch']);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId)
                    ->orWhere('destination_branch_id', $branchId);
            });
        }

        return $query->latest()->limit($limit)->get();
    }

    public function getExpenseChartData(?int $branchId = null): array
    {
        $labels = [];
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');

            $query = Expense::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            $data[] = (float) $query->sum('amount');
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function getWeeklyTrend(?int $branchId = null): array
    {
        $labels = [];
        $shipments = [];
        $deliveries = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subWeeks($i);
            $start = $date->startOfWeek()->copy();
            $end = $date->endOfWeek()->copy();
            $labels[] = 'Week ' . $start->format('M d');

            $sQuery = Shipment::whereBetween('created_at', [$start, $end]);
            $dQuery = Delivery::whereBetween('created_at', [$start, $end]);

            if ($branchId) {
                $sQuery->where(function ($q) use ($branchId) {
                    $q->where('origin_branch_id', $branchId)->orWhere('destination_branch_id', $branchId);
                });
                $dQuery->whereHas('shipment', function ($q) use ($branchId) {
                    $q->where('origin_branch_id', $branchId)->orWhere('destination_branch_id', $branchId);
                });
            }

            $shipments[] = $sQuery->count();
            $deliveries[] = $dQuery->count();
        }

        return ['labels' => $labels, 'shipments' => $shipments, 'deliveries' => $deliveries];
    }

    public function getLowStockItems(int $limit = 10, ?int $branchId = null): Collection
    {
        $query = InventoryItem::with('warehouse')
            ->whereColumn('quantity', '<=', 'min_quantity')
            ->where('is_active', true)
            ->orderBy('quantity');

        if ($branchId) {
            $query->whereHas('warehouse', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        return $query->limit($limit)->get();
    }

    public function getVehicleStatusBreakdown(?int $branchId = null): array
    {
        $query = Vehicle::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $active = (clone $query)->where('status', 'active')->count();
        $maintenance = (clone $query)->whereHas('vehicleMaintenances', function ($q) {
            $q->where('status', 'in_progress');
        })->count();
        $available = (clone $query)->where('status', 'available')->count();
        $total = (clone $query)->count();

        return [
            'active' => $active,
            'maintenance' => $maintenance,
            'available' => $available,
            'inactive' => max(0, $total - $active - $maintenance - $available),
            'total' => $total,
        ];
    }

    public function getDeliveryTrendData(?int $branchId = null): array
    {
        $labels = [];
        $onTime = [];
        $delayed = [];

        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i * 2);
            $labels[] = $date->format('M d');

            $query = Delivery::whereDate('created_at', $date);

            if ($branchId) {
                $query->whereHas('shipment', function ($q) use ($branchId) {
                    $q->where('origin_branch_id', $branchId)->orWhere('destination_branch_id', $branchId);
                });
            }

            $total = (clone $query)->count();
            $delivered = (clone $query)->where('status', 'delivered')->count();
            $onTime[] = $delivered;
            $delayed[] = max(0, $total - $delivered);
        }

        return ['labels' => $labels, 'onTime' => $onTime, 'delayed' => $delayed];
    }

    public function getMonthlyComparison(?int $branchId = null): array
    {
        $labels = [];
        $revenue = [];
        $expenses = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');

            $rQuery = Invoice::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month);
            $eQuery = Expense::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month);

            if ($branchId) {
                $rQuery->where('branch_id', $branchId);
                $eQuery->where('branch_id', $branchId);
            }

            $revenue[] = (float) $rQuery->sum('total');
            $expenses[] = (float) $eQuery->sum('amount');
        }

        return ['labels' => $labels, 'revenue' => $revenue, 'expenses' => $expenses];
    }

    public function getDriverStats(?int $branchId = null): array
    {
        $query = Driver::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return [
            'total' => (clone $query)->count(),
            'available' => (clone $query)->where('status', 'available')->count(),
            'on_delivery' => (clone $query)->whereHas('deliveries', function ($q) {
                $q->whereIn('status', ['assigned', 'in_transit']);
            })->count(),
        ];
    }

    public function getWarehouseUtilization(?int $branchId = null): array
    {
        $query = Warehouse::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $warehouses = $query->get();
        $labels = [];
        $used = [];
        $available = [];

        foreach ($warehouses as $warehouse) {
            $labels[] = $warehouse->name;
            $used[] = (float) $warehouse->used_capacity;
            $available[] = (float) ($warehouse->total_capacity - $warehouse->used_capacity);
        }

        return ['labels' => $labels, 'used' => $used, 'available' => $available];
    }

    public function getShipmentTypeBreakdown(?int $branchId = null): array
    {
        $query = Shipment::query();

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId)->orWhere('destination_branch_id', $branchId);
            });
        }

        return $query->selectRaw('shipment_type, COUNT(*) as count')
            ->groupBy('shipment_type')
            ->pluck('count', 'shipment_type')
            ->toArray();
    }
}
