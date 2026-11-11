<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Dispatch;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Shipment;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();

        $totalShipmentsToday = Shipment::whereDate('created_at', $today)->count();
        $pendingShipments = Shipment::where('status', 'pending')->count();
        $deliveredShipments = Shipment::where('status', 'delivered')->count();
        $cancelledShipments = Shipment::where('status', 'cancelled')->count();

        $totalRevenue = Invoice::where('status', 'paid')->sum('total_amount');
        $totalExpenses = Expense::where('status', 'approved')->sum('total_amount');

        $monthlyShipments = Shipment::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('YEAR(created_at) as year'),
            DB::raw('COUNT(*) as total')
        )
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Carbon::createFromDate($item->year, $item->month, 1)->format('M Y'),
                    'total' => $item->total,
                ];
            });

        $statusDistribution = Shipment::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentShipments = Shipment::with('customer', 'branch')
            ->latest()
            ->limit(10)
            ->get();

        $stats = [
            'total_shipments_today' => $totalShipmentsToday,
            'pending_shipments' => $pendingShipments,
            'delivered_shipments' => $deliveredShipments,
            'cancelled_shipments' => $cancelledShipments,
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'total_customers' => Customer::count(),
            'total_vehicles' => Vehicle::count(),
            'total_drivers' => Driver::count(),
            'active_shipments' => Shipment::whereNotIn('status', ['delivered', 'cancelled'])->count(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'stats' => $stats,
                'monthly_shipments' => $monthlyShipments,
                'status_distribution' => $statusDistribution,
                'recent_shipments' => $recentShipments,
            ]);
        }

        return view('dashboard.index', compact(
            'stats',
            'monthlyShipments',
            'statusDistribution',
            'recentShipments'
        ));
    }
}
