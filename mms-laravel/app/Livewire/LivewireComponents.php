<?php
// ============================================================
// app/Livewire/Dashboard/KpiCards.php
// ============================================================
namespace App\Livewire\Dashboard;

use App\Models\WorkOrder;
use Livewire\Component;
use Livewire\Attributes\Polling;

#[Polling('30s')]
class KpiCards extends Component
{
    public array $stats = [];

    public function mount(): void { $this->loadStats(); }
    public function loadStats(): void
    {
        $this->stats = [
            'open'      => WorkOrder::open()->count(),
            'overdue'   => WorkOrder::overdue()->count(),
            'completed' => WorkOrder::whereDate('completed_at', today())->count(),
            'in_prog'   => WorkOrder::where('status', 'in_progress')->count(),
        ];
    }

    public function render() { return view('livewire.dashboard.kpi-cards'); }
}


// ============================================================
// app/Livewire/Notifications/NotificationBell.php
// ============================================================
namespace App\Livewire\Notifications;

use Livewire\Component;
use Livewire\Attributes\On;

class NotificationBell extends Component
{
    public bool $open = false;

    protected $listeners = [
        'echo-private:App.Models.User.{id},Illuminate\\Notifications\\Events\\BroadcastNotificationCreated' => 'refresh',
    ];

    public function getNotificationsProperty()
    {
        return auth()->user()->unreadNotifications()->latest()->limit(10)->get();
    }

    public function getCountProperty(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        $this->dispatch('$refresh');
    }

    public function markRead(string $id): void
    {
        auth()->user()->notifications()->findOrFail($id)->markAsRead();
    }

    public function render()
    {
        return view('livewire.notifications.notification-bell');
    }
}


// ============================================================
// app/Livewire/WorkOrders/WorkOrderForm.php
// ============================================================
namespace App\Livewire\WorkOrders;

use App\Models\{WorkOrder, Asset, User};
use App\Services\WorkOrderService;
use Livewire\Component;

class WorkOrderForm extends Component
{
    public ?WorkOrder $workOrder = null;

    // Form fields
    public string $title       = '';
    public string $description = '';
    public string $type        = 'corrective';
    public string $priority    = 'medium';
    public int    $asset_id    = 0;
    public ?int   $assigned_to = null;
    public string $due_at      = '';

    protected function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'type'        => 'required|in:corrective,preventive,inspection,emergency',
            'priority'    => 'required|in:low,medium,high,critical',
            'asset_id'    => 'required|exists:assets,id',
            'assigned_to' => 'nullable|exists:users,id',
            'due_at'      => 'nullable|date|after:now',
        ];
    }

    public function mount(?WorkOrder $workOrder = null): void
    {
        if ($workOrder?->id) {
            $this->workOrder   = $workOrder;
            $this->title       = $workOrder->title;
            $this->description = $workOrder->description ?? '';
            $this->type        = $workOrder->type;
            $this->priority    = $workOrder->priority;
            $this->asset_id    = $workOrder->asset_id;
            $this->assigned_to = $workOrder->assigned_to;
            $this->due_at      = $workOrder->due_at?->format('Y-m-d\TH:i') ?? '';
        }
    }

    public function save(WorkOrderService $service): void
    {
        $data = $this->validate();
        if ($this->due_at) $data['due_at'] = $this->due_at;

        if ($this->workOrder) {
            $this->workOrder->update($data);
            $this->dispatch('notify', message: 'Work order updated!', type: 'success');
        } else {
            $wo = $service->create($data, auth()->user());
            $this->dispatch('notify', message: "Work order {$wo->code} created!", type: 'success');
            $this->reset(['title','description','due_at']);
        }
    }

    public function render()
    {
        $assets      = Asset::active()->orderBy('name')->get();
        $technicians = User::active()->role('technician')->orderBy('name')->get();
        return view('livewire.work-orders.work-order-form', compact('assets', 'technicians'));
    }
}
