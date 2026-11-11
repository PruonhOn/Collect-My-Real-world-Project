<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\StockMovement;
use Illuminate\Support\Collection;

class ReportService
{
    public function generateShipmentReport(?array $filters = null): array
    {
        $query = Shipment::query()->with(['customer', 'originBranch', 'destinationBranch']);

        if (isset($filters['date_from'])) {
            $query->whereDate('shipment_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('shipment_date', '<=', $filters['date_to']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['origin_branch_id'])) {
            $query->where('origin_branch_id', $filters['origin_branch_id']);
        }

        if (isset($filters['destination_branch_id'])) {
            $query->where('destination_branch_id', $filters['destination_branch_id']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        $shipments = $query->orderByDesc('shipment_date')->get();

        return [
            'total_shipments' => $shipments->count(),
            'total_weight' => $shipments->sum('weight'),
            'total_volume' => $shipments->sum('volume'),
            'total_declared_value' => $shipments->sum('declared_value'),
            'by_status' => $shipments->groupBy('status')->map->count(),
            'express_count' => $shipments->where('is_express', true)->count(),
            'insured_count' => $shipments->where('is_insured', true)->count(),
            'shipments' => $shipments,
        ];
    }

    public function generateRevenueReport(?array $filters = null): array
    {
        $query = Invoice::query()->with(['customer', 'branch']);

        if (isset($filters['date_from'])) {
            $query->whereDate('invoice_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('invoice_date', '<=', $filters['date_to']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        $invoices = $query->orderByDesc('invoice_date')->get();

        return [
            'total_invoiced' => $invoices->sum('total'),
            'total_paid' => $invoices->sum('paid_amount'),
            'total_outstanding' => $invoices->sum('balance'),
            'total_tax' => $invoices->sum('tax'),
            'total_discount' => $invoices->sum('discount'),
            'invoice_count' => $invoices->count(),
            'by_status' => $invoices->groupBy('status')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('total'),
                ];
            }),
            'invoices' => $invoices,
        ];
    }

    public function generateExpenseReport(?array $filters = null): array
    {
        $query = Expense::query()->with(['branch', 'vehicle', 'createdBy']);

        if (isset($filters['date_from'])) {
            $query->whereDate('expense_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('expense_date', '<=', $filters['date_to']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $expenses = $query->orderByDesc('expense_date')->get();

        return [
            'total_amount' => $expenses->sum('amount'),
            'total_count' => $expenses->count(),
            'by_category' => $expenses->groupBy('category')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                ];
            }),
            'recurring_total' => $expenses->where('is_recurring', true)->sum('amount'),
            'expenses' => $expenses,
        ];
    }

    public function generateInventoryReport(?array $filters = null): array
    {
        $query = InventoryItem::query()->with(['warehouse', 'location']);

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $query->whereColumn('quantity', '<=', 'min_quantity');
        }

        $items = $query->get();

        return [
            'total_items' => $items->count(),
            'total_quantity' => $items->sum('quantity'),
            'total_value' => $items->sum(fn($item) => $item->quantity * $item->unit_price),
            'low_stock_items' => $items->filter(fn($item) => $item->quantity <= $item->min_quantity)->count(),
            'by_warehouse' => $items->groupBy('warehouse_id')->map(function ($group) {
                return [
                    'warehouse' => $group->first()->warehouse?->name,
                    'count' => $group->count(),
                    'total_value' => $group->sum(fn($i) => $i->quantity * $i->unit_price),
                ];
            }),
            'items' => $items,
        ];
    }

    public function generateDriverPerformanceReport(?array $filters = null): array
    {
        $query = Driver::query()->with(['branch', 'deliveries']);

        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $drivers = $query->get()->map(function ($driver) {
            $deliveries = $driver->deliveries;

            return [
                'driver' => $driver,
                'total_deliveries' => $deliveries->count(),
                'delivered' => $deliveries->where('status', 'delivered')->count(),
                'failed' => $deliveries->where('status', 'failed')->count(),
                'in_transit' => $deliveries->where('status', 'in_transit')->count(),
                'success_rate' => $deliveries->count() > 0
                    ? round(($deliveries->where('status', 'delivered')->count() / $deliveries->count()) * 100, 2)
                    : 0,
            ];
        });

        return [
            'total_drivers' => $drivers->count(),
            'average_success_rate' => $drivers->avg('success_rate'),
            'drivers' => $drivers,
        ];
    }

    public function generateCustomerReport(?array $filters = null): array
    {
        $query = Customer::query()->with(['shipments', 'invoices', 'payments']);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $customers = $query->get()->map(function ($customer) {
            return [
                'customer' => $customer,
                'total_shipments' => $customer->shipments->count(),
                'total_invoiced' => $customer->invoices->sum('total'),
                'total_paid' => $customer->payments->sum('amount'),
                'outstanding_balance' => $customer->outstanding_balance,
                'last_shipment_date' => $customer->shipments->sortByDesc('created_at')->first()?->created_at,
            ];
        });

        return [
            'total_customers' => $customers->count(),
            'active_customers' => $customers->where('customer.is_active', true)->count(),
            'total_outstanding' => $customers->sum('outstanding_balance'),
            'customers' => $customers->sortByDesc('total_invoiced')->values(),
        ];
    }

    public function generateStockMovementReport(?array $filters = null): array
    {
        $query = StockMovement::query()->with(['inventoryItem', 'createdBy', 'fromLocation', 'toLocation']);

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['inventory_item_id'])) {
            $query->where('inventory_item_id', $filters['inventory_item_id']);
        }

        $movements = $query->orderByDesc('created_at')->get();

        return [
            'total_movements' => $movements->count(),
            'total_quantity_in' => $movements->where('type', 'in')->sum('quantity'),
            'total_quantity_out' => $movements->where('type', 'out')->sum('quantity'),
            'total_transfers' => $movements->where('type', 'transfer')->count(),
            'by_type' => $movements->groupBy('type')->map->count(),
            'movements' => $movements,
        ];
    }
}
