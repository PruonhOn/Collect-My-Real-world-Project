<?php
// ============================================================
// app/Jobs/NotifyOverdueWorkOrders.php
// ============================================================
namespace App\Jobs;

use App\Services\WorkOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};

class NotifyOverdueWorkOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(WorkOrderService $service): void
    {
        $count = $service->notifyOverdue();
        logger("Notified {$count} overdue work orders.");
    }
}


// ============================================================
// app/Events/WorkOrderCreated.php
// ============================================================
namespace App\Events;

use App\Models\WorkOrder;
use Illuminate\Broadcasting\{Channel, InteractsWithSockets};
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkOrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public WorkOrder $workOrder) {}

    public function broadcastOn(): Channel
    {
        return new Channel('work-orders');
    }

    public function broadcastWith(): array
    {
        return [
            'id'       => $this->workOrder->id,
            'code'     => $this->workOrder->code,
            'title'    => $this->workOrder->title,
            'priority' => $this->workOrder->priority,
        ];
    }
}


// ============================================================
// app/Events/WorkOrderUpdated.php
// ============================================================
namespace App\Events;

use App\Models\WorkOrder;
use Illuminate\Broadcasting\{Channel, InteractsWithSockets};
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkOrderUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public WorkOrder $workOrder) {}

    public function broadcastOn(): Channel
    {
        return new Channel('work-orders');
    }

    public function broadcastWith(): array
    {
        return [
            'id'     => $this->workOrder->id,
            'code'   => $this->workOrder->code,
            'status' => $this->workOrder->status,
        ];
    }
}


// ============================================================
// app/Exports/WorkOrdersExport.php
// ============================================================
namespace App\Exports;

use App\Models\WorkOrder;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping, WithStyles};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkOrdersExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private array $filters = []) {}

    public function collection()
    {
        return WorkOrder::with(['asset', 'technician'])
            ->filter($this->filters)
            ->get();
    }

    public function headings(): array
    {
        return ['Code', 'Title', 'Asset', 'Type', 'Priority', 'Status', 'Technician', 'Due', 'Completed', 'Progress'];
    }

    public function map($wo): array
    {
        return [
            $wo->code,
            $wo->title,
            $wo->asset->name,
            ucfirst($wo->type),
            ucfirst($wo->priority),
            ucwords(str_replace('_', ' ', $wo->status)),
            $wo->technician?->name ?? 'Unassigned',
            $wo->due_at?->format('Y-m-d'),
            $wo->completed_at?->format('Y-m-d'),
            $wo->progress . '%',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1E2A3B']]],
        ];
    }
}
