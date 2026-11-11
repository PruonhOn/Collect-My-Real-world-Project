<?php
// ============================================================
// app/Services/WorkOrderService.php
// ============================================================
namespace App\Services;

use App\Models\{WorkOrder, User, Alert};
use App\Notifications\{WorkOrderAssigned, WorkOrderOverdue};
use Illuminate\Support\Facades\Cache;

class WorkOrderService
{
    /**
     * Create a work order and trigger assignment if provided.
     */
    public function create(array $data, User $creator): WorkOrder
    {
        $wo = WorkOrder::create([...$data, 'created_by' => $creator->id]);

        if (!empty($data['assigned_to'])) {
            $wo->assign(User::findOrFail($data['assigned_to']));
        }

        // Fire broadcast event
        event(new \App\Events\WorkOrderCreated($wo));

        Cache::forget('dashboard.kpis');
        return $wo;
    }

    /**
     * Notify all technicians of overdue work orders (called from scheduler).
     */
    public function notifyOverdue(): int
    {
        $overdue = WorkOrder::overdue()->with('technician')->get();

        foreach ($overdue as $wo) {
            if ($wo->technician) {
                $wo->technician->notify(new WorkOrderOverdue($wo));
            }

            Alert::firstOrCreate(
                ['alertable_type' => WorkOrder::class, 'alertable_id' => $wo->id, 'severity' => 'critical'],
                ['title' => "Overdue: {$wo->code}", 'message' => $wo->title]
            );
        }

        return $overdue->count();
    }

    /**
     * Get dashboard stats (with caching).
     */
    public function getDashboardStats(): array
    {
        return Cache::remember('dashboard.kpis', 60, function () {
            return [
                'open'            => WorkOrder::open()->count(),
                'overdue'         => WorkOrder::overdue()->count(),
                'completed_today' => WorkOrder::whereDate('completed_at', today())->count(),
                'in_progress'     => WorkOrder::where('status', 'in_progress')->count(),
            ];
        });
    }
}


// ============================================================
// app/Services/ReportService.php
// ============================================================
namespace App\Services;

use App\Models\{WorkOrder, Asset};
use Illuminate\Support\Collection;

class ReportService
{
    public function workOrderSummary(string $from, string $to): array
    {
        $orders = WorkOrder::with(['asset', 'technician'])
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $byPriority = $orders->groupBy('priority')->map->count();
        $byStatus   = $orders->groupBy('status')->map->count();
        $byTech     = $orders->whereNotNull('assigned_to')
                              ->groupBy('technician.name')
                              ->map->count();

        $avgResolve = $orders->filter(fn($o) => $o->completed_at && $o->started_at)
            ->avg(fn($o) => $o->started_at->diffInHours($o->completed_at));

        return compact('orders', 'byPriority', 'byStatus', 'byTech', 'avgResolve');
    }

    public function assetHealthReport(): Collection
    {
        return Asset::active()
            ->withCount([
                'workOrders',
                'workOrders as open_count'      => fn($q) => $q->whereNotIn('status', ['completed','cancelled']),
                'workOrders as overdue_count'   => fn($q) => $q->overdue(),
                'workOrders as completed_count' => fn($q) => $q->where('status', 'completed'),
            ])
            ->orderByDesc('overdue_count')
            ->get();
    }
}
