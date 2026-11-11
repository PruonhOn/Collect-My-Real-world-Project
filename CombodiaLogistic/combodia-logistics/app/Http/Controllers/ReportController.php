<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function daily(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $carbonDate = Carbon::parse($date);

        $shipments = Shipment::whereDate('created_at', $carbonDate)->count();
        $delivered = Shipment::whereDate('created_at', $carbonDate)->where('status', 'delivered')->count();
        $revenue = Invoice::whereDate('created_at', $carbonDate)->where('status', 'paid')->sum('total_amount');
        $expenses = Expense::whereDate('expense_date', $carbonDate)->where('status', 'approved')->sum('total_amount');
        $income = Income::whereDate('income_date', $carbonDate)->where('status', 'received')->sum('total_amount');

        $data = compact('date', 'shipments', 'delivered', 'revenue', 'expenses', 'income');

        if ($request->ajax()) {
            return response()->json($data);
        }

        return view('reports.daily', $data);
    }

    public function weekly(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfWeek()->format('Y-m-d'));

        $shipments = Shipment::whereBetween('created_at', [$startDate, $endDate])->count();
        $delivered = Shipment::whereBetween('created_at', [$startDate, $endDate])->where('status', 'delivered')->count();
        $revenue = Invoice::whereBetween('created_at', [$startDate, $endDate])->where('status', 'paid')->sum('total_amount');
        $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->where('status', 'approved')->sum('total_amount');
        $income = Income::whereBetween('income_date', [$startDate, $endDate])->where('status', 'received')->sum('total_amount');

        $dailyData = Shipment::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->get();

        $data = compact('startDate', 'endDate', 'shipments', 'delivered', 'revenue', 'expenses', 'income', 'dailyData');

        if ($request->ajax()) {
            return response()->json($data);
        }

        return view('reports.weekly', $data);
    }

    public function monthly(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        $shipments = Shipment::whereBetween('created_at', [$startDate, $endDate])->count();
        $delivered = Shipment::whereBetween('created_at', [$startDate, $endDate])->where('status', 'delivered')->count();
        $revenue = Invoice::whereBetween('created_at', [$startDate, $endDate])->where('status', 'paid')->sum('total_amount');
        $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->where('status', 'approved')->sum('total_amount');
        $income = Income::whereBetween('income_date', [$startDate, $endDate])->where('status', 'received')->sum('total_amount');

        $data = compact('month', 'shipments', 'delivered', 'revenue', 'expenses', 'income');

        if ($request->ajax()) {
            return response()->json($data);
        }

        return view('reports.monthly', $data);
    }

    public function yearly(Request $request)
    {
        $year = $request->get('year', Carbon::now()->format('Y'));

        $shipments = Shipment::whereYear('created_at', $year)->count();
        $delivered = Shipment::whereYear('created_at', $year)->where('status', 'delivered')->count();
        $revenue = Invoice::whereYear('created_at', $year)->where('status', 'paid')->sum('total_amount');
        $expenses = Expense::whereYear('expense_date', $year)->where('status', 'approved')->sum('total_amount');
        $income = Income::whereYear('income_date', $year)->where('status', 'received')->sum('total_amount');

        $monthlyData = Shipment::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as total')
        )
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $data = compact('year', 'shipments', 'delivered', 'revenue', 'expenses', 'income', 'monthlyData');

        if ($request->ajax()) {
            return response()->json($data);
        }

        return view('reports.yearly', $data);
    }

    public function shipment(Request $request)
    {
        $query = Shipment::with(['customer', 'branch']);

        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && $request->date_from !== null) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to !== null) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $shipments = $query->latest()->paginate(20);

        $statusCounts = Shipment::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $data = compact('shipments', 'statusCounts');

        if ($request->ajax()) {
            return response()->json($data);
        }

        return view('reports.shipment', $data);
    }

    public function financial(Request $request)
    {
        $year = $request->get('year', Carbon::now()->format('Y'));

        $totalRevenue = Invoice::whereYear('created_at', $year)->where('status', 'paid')->sum('total_amount');
        $totalExpenses = Expense::whereYear('expense_date', $year)->where('status', 'approved')->sum('total_amount');
        $totalIncome = Income::whereYear('income_date', $year)->where('status', 'received')->sum('total_amount');
        $profit = $totalIncome - $totalExpenses;

        $monthlyRevenue = Invoice::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(total_amount) as total')
        )
            ->whereYear('created_at', $year)
            ->where('status', 'paid')
            ->groupBy('month')
            ->pluck('total', 'month');

        $monthlyExpenses = Expense::select(
            DB::raw('MONTH(expense_date) as month'),
            DB::raw('SUM(total_amount) as total')
        )
            ->whereYear('expense_date', $year)
            ->where('status', 'approved')
            ->groupBy('month')
            ->pluck('total', 'month');

        $data = compact('year', 'totalRevenue', 'totalExpenses', 'totalIncome', 'profit', 'monthlyRevenue', 'monthlyExpenses');

        if ($request->ajax()) {
            return response()->json($data);
        }

        return view('reports.financial', $data);
    }

    public function inventory(Request $request)
    {
        $query = Inventory::with(['warehouse', 'warehouseShelf']);

        if ($request->has('warehouse_id') && $request->warehouse_id !== null) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $inventories = $query->latest()->paginate(20);

        $lowStockItems = Inventory::whereColumn('quantity', '<=', 'reorder_point')->count();
        $outOfStock = Inventory::where('quantity', '<=', 0)->count();
        $totalValue = Inventory::sum(DB::raw('quantity * unit_cost'));

        $data = compact('inventories', 'lowStockItems', 'outOfStock', 'totalValue');

        if ($request->ajax()) {
            return response()->json($data);
        }

        return view('reports.inventory', $data);
    }

    public function driver(Request $request)
    {
        $drivers = Driver::with('branch')->latest()->paginate(20);

        $activeDrivers = Driver::where('status', 'active')->count();
        $onLeaveDrivers = Driver::where('status', 'on_leave')->count();

        $data = compact('drivers', 'activeDrivers', 'onLeaveDrivers');

        if ($request->ajax()) {
            return response()->json($data);
        }

        return view('reports.driver', $data);
    }

    public function warehouse(Request $request)
    {
        $warehouses = Warehouse::with(['branch', 'warehouseZones'])->latest()->paginate(20);

        $totalCapacity = Warehouse::sum('capacity');
        $usedCapacity = Warehouse::sum('used_capacity');
        $availableCapacity = $totalCapacity - $usedCapacity;

        $data = compact('warehouses', 'totalCapacity', 'usedCapacity', 'availableCapacity');

        if ($request->ajax()) {
            return response()->json($data);
        }

        return view('reports.warehouse', $data);
    }

    public function customer(Request $request)
    {
        $customers = Customer::with('user')->latest()->paginate(20);

        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('is_active', true)->count();
        $totalBalance = Customer::sum('balance');

        $data = compact('customers', 'totalCustomers', 'activeCustomers', 'totalBalance');

        if ($request->ajax()) {
            return response()->json($data);
        }

        return view('reports.customer', $data);
    }

    public function vehicle(Request $request)
    {
        $vehicles = Vehicle::with(['branch', 'vehicleType'])->latest()->paginate(20);

        $totalVehicles = Vehicle::count();
        $activeVehicles = Vehicle::where('status', 'active')->count();
        $maintenanceVehicles = Vehicle::where('status', 'maintenance')->count();

        $data = compact('vehicles', 'totalVehicles', 'activeVehicles', 'maintenanceVehicles');

        if ($request->ajax()) {
            return response()->json($data);
        }

        return view('reports.vehicle', $data);
    }

    public function profitLoss(Request $request)
    {
        $year = $request->get('year', Carbon::now()->format('Y'));

        $totalIncome = Income::whereYear('income_date', $year)->where('status', 'received')->sum('total_amount');
        $totalExpenses = Expense::whereYear('expense_date', $year)->where('status', 'approved')->sum('total_amount');
        $netProfit = $totalIncome - $totalExpenses;

        $monthlyData = collect();
        for ($month = 1; $month <= 12; $month++) {
            $income = Income::whereYear('income_date', $year)
                ->whereMonth('income_date', $month)
                ->where('status', 'received')
                ->sum('total_amount');
            $expense = Expense::whereYear('expense_date', $year)
                ->whereMonth('expense_date', $month)
                ->where('status', 'approved')
                ->sum('total_amount');
            $monthlyData->push([
                'month' => Carbon::createFromDate($year, $month, 1)->format('M'),
                'income' => $income,
                'expense' => $expense,
                'profit' => $income - $expense,
            ]);
        }

        $data = compact('year', 'totalIncome', 'totalExpenses', 'netProfit', 'monthlyData');

        if ($request->ajax()) {
            return response()->json($data);
        }

        return view('reports.profit-loss', $data);
    }
}
