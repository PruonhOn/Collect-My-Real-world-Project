<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index()
    {
        $stats = $this->dashboardService->getStats();
        $shipmentChart = $this->dashboardService->getShipmentChartData();
        $revenueChart = $this->dashboardService->getRevenueChartData();
        $expenseChart = $this->dashboardService->getExpenseChartData();
        $weeklyTrend = $this->dashboardService->getWeeklyTrend();
        $recentShipments = $this->dashboardService->getRecentShipments();
        $topCustomers = $this->dashboardService->getTopCustomers();
        $shipmentsByStatus = $this->dashboardService->getShipmentsByStatus();
        $recentActivities = $this->dashboardService->getRecentActivity();
        $lowStockItems = $this->dashboardService->getLowStockItems();
        $vehicleStatus = $this->dashboardService->getVehicleStatusBreakdown();
        $deliveryTrend = $this->dashboardService->getDeliveryTrendData();
        $monthlyComparison = $this->dashboardService->getMonthlyComparison();
        $driverStats = $this->dashboardService->getDriverStats();
        $warehouseUtilization = $this->dashboardService->getWarehouseUtilization();
        $shipmentTypeBreakdown = $this->dashboardService->getShipmentTypeBreakdown();
        $topCustomersData = $topCustomers->pluck('total_amount')->toArray();
        $topCustomersLabels = $topCustomers->pluck('customer.name')->toArray();
        $revenueStats = $this->dashboardService->getRevenueStats();
        $expenseStats = $this->dashboardService->getExpenseStats();
        $deliveryStats = $this->dashboardService->getDeliveryStats();

        $data = compact(
            'stats', 'shipmentChart', 'revenueChart', 'expenseChart',
            'weeklyTrend', 'recentShipments', 'topCustomers',
            'shipmentsByStatus', 'recentActivities', 'lowStockItems',
            'vehicleStatus', 'deliveryTrend', 'monthlyComparison',
            'driverStats', 'warehouseUtilization', 'shipmentTypeBreakdown',
            'topCustomersData', 'topCustomersLabels',
            'revenueStats', 'expenseStats', 'deliveryStats'
        ) + [
            'totalShipments' => $stats['total_shipments'],
            'shipmentsGrowth' => $stats['shipments_growth'],
            'activeDeliveries' => $stats['active_deliveries'],
            'warehouseStock' => $stats['warehouse_stock'],
            'warehouseCount' => $stats['warehouse_count'],
            'monthlyRevenue' => $stats['monthly_revenue'],
            'revenueGrowth' => $stats['revenue_growth'],
            'monthlyExpenses' => $stats['monthly_expenses'],
            'expenseChange' => $stats['expense_change'],
            'activeVehicles' => $stats['active_vehicles'],
            'totalVehicles' => $stats['total_vehicles'],
            'successRate' => $stats['success_rate'],
            'pendingRate' => $stats['pending_rate'],
            'failedRate' => $stats['failed_rate'],
            'dailyShipmentsLabels' => $shipmentChart['labels'],
            'dailyShipmentsData' => $shipmentChart['data'],
            'monthlyRevenueLabels' => $revenueChart['labels'],
            'monthlyRevenueData' => $revenueChart['data'],
        ];

        $routeName = request()->route()->getName();

        if ($routeName === 'admin.home' || $routeName === 'admin.dashboard') {
            return view('admin.dashboard', $data);
        }

        return view('dashboard', $data);
    }
}
