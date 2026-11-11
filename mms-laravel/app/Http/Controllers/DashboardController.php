<?php

namespace App\Http\Controllers;

use App\Models\{WorkOrder, Asset, Alert, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Main dashboard — cached KPIs + chart data
     */
    public function index()
    {
        $kpis    = $this->getKpis();
        $charts  = $this->getChartData();
        $alerts  = Alert::with('alertable')
                        ->where('read_at', null)
                        ->latest()
                        ->limit(10)
                        ->get();

        $recentOrders = WorkOrder::with(['asset', 'technician'])
                                 ->latest()
                                 ->limit(10)
                                 ->get();

        return view('dashboard.index', compact('kpis', 'charts', 'alerts', 'recentOrders'));
    }

    // ----------------------------------------------------------------
    // KPI cards (cached 60s — refreshed via Livewire polling)
    // ----------------------------------------------------------------
    private function getKpis(): array
    {
        return Cache::remember('dashboard.kpis', 60, function () {
            $now    = now();
            $mtdStart = $now->copy()->startOfMonth();
            $lastMtdStart = $now->copy()->subMonth()->startOfMonth();
            $lastMtdEnd   = $now->copy()->subMonth()->endOfMonth();

            $completedMtd    = WorkOrder::whereBetween('completed_at', [$mtdStart, $now])->count();
            $completedLastMtd = WorkOrder::whereBetween('completed_at', [$lastMtdStart, $lastMtdEnd])->count();

            $avgResolveHours = WorkOrder::whereNotNull('completed_at')
                ->whereNotNull('started_at')
                ->whereBetween('completed_at', [$mtdStart, $now])
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_mins')
                ->value('avg_mins');

            return [
                'open_orders'     => WorkOrder::open()->count(),
                'overdue'         => WorkOrder::overdue()->count(),
                'completed_mtd'   => $completedMtd,
                'completed_change' => $completedLastMtd
                    ? round((($completedMtd - $completedLastMtd) / $completedLastMtd) * 100)
                    : 0,
                'avg_resolve_hours' => $avgResolveHours
                    ? round($avgResolveHours / 60, 1)
                    : 0,
                'assets_total'    => Asset::active()->count(),
            ];
        });
    }

    // ----------------------------------------------------------------
    // Chart data — status breakdown + 30-day completion trend
    // ----------------------------------------------------------------
    private function getChartData(): array
    {
        return Cache::remember('dashboard.charts', 300, function () {
            // Status distribution
            $statusCounts = WorkOrder::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // 30-day completion trend
            $trend = WorkOrder::selectRaw('DATE(completed_at) as date, COUNT(*) as count')
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray();

            // Priority distribution
            $priorityCounts = WorkOrder::whereIn('status', ['open', 'assigned', 'in_progress'])
                ->selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray();

            return compact('statusCounts', 'trend', 'priorityCounts');
        });
    }

    /**
     * AJAX endpoint for real-time KPI refresh (called by Livewire polling)
     */
    public function kpisJson(): \Illuminate\Http\JsonResponse
    {
        Cache::forget('dashboard.kpis');
        return response()->json($this->getKpis());
    }
}
