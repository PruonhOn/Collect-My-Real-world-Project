<?php

namespace App\Livewire\WorkOrders;

use App\Models\WorkOrder;
use App\Models\Asset;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{On, Url};

class WorkOrderTable extends Component
{
    use WithPagination;

    // ----------------------------------------------------------------
    // Bound to URL query string for shareable filtered views
    // ----------------------------------------------------------------
    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $priorityFilter = '';

    #[Url]
    public string $sortField = 'due_at';

    #[Url]
    public string $sortDir = 'asc';

    public bool $showCreateModal = false;

    // ----------------------------------------------------------------
    // Listeners — receive real-time broadcast events via Reverb
    // ----------------------------------------------------------------
    protected $listeners = [
        'echo:work-orders,WorkOrderCreated'  => '$refresh',
        'echo:work-orders,WorkOrderUpdated'  => '$refresh',
        'workOrderCreated'                   => '$refresh',
    ];

    // ----------------------------------------------------------------
    // Reset pagination on filter changes
    // ----------------------------------------------------------------
    public function updatedSearch(): void         { $this->resetPage(); }
    public function updatedStatusFilter(): void   { $this->resetPage(); }
    public function updatedPriorityFilter(): void { $this->resetPage(); }

    public function sort(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'asc';
        }
    }

    // ----------------------------------------------------------------
    // Quick status update (drag-drop or dropdown)
    // ----------------------------------------------------------------
    public function updateStatus(int $id, string $newStatus): void
    {
        $this->authorize('update', WorkOrder::findOrFail($id));

        WorkOrder::findOrFail($id)->update(['status' => $newStatus]);
        $this->dispatch('notify', message: 'Status updated', type: 'success');
    }

    public function updateProgress(int $id, int $progress): void
    {
        $wo = WorkOrder::findOrFail($id);
        $this->authorize('update', $wo);
        $wo->update(['progress' => $progress]);
    }

    public function delete(int $id): void
    {
        $this->authorize('delete', WorkOrder::findOrFail($id));
        WorkOrder::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Work order deleted', type: 'warning');
    }

    // ----------------------------------------------------------------
    // Render
    // ----------------------------------------------------------------
    public function render()
    {
        $workOrders = WorkOrder::query()
            ->with(['asset', 'technician', 'creator'])
            ->filter([
                'search'   => $this->search,
                'status'   => $this->statusFilter,
                'priority' => $this->priorityFilter,
            ])
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate(15);

        $assets = Asset::active()->get(['id', 'name', 'code']);
        $technicians = User::role('technician')->get(['id', 'name']);

        return view('livewire.work-orders.work-order-table', compact(
            'workOrders', 'assets', 'technicians'
        ));
    }
}
