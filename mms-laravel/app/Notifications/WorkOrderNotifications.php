<?php
// ============================================================
// WorkOrderAssigned.php — Email + DB + Broadcast notification
// ============================================================

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\{Notification, Messages\MailMessage, Messages\BroadcastMessage};

class WorkOrderAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WorkOrder $workOrder) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Work Order Assigned: {$this->workOrder->code}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been assigned work order **{$this->workOrder->code}**.")
            ->line("**Title:** {$this->workOrder->title}")
            ->line("**Priority:** {$this->workOrder->priority}")
            ->line("**Due:** " . ($this->workOrder->due_at?->format('M d, Y H:i') ?? 'No deadline'))
            ->action('View Work Order', route('work-orders.show', $this->workOrder))
            ->line('Please review and begin work at your earliest convenience.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'         => "Assigned: {$this->workOrder->code}",
            'message'       => $this->workOrder->title,
            'work_order_id' => $this->workOrder->id,
            'priority'      => $this->workOrder->priority,
            'due_at'        => $this->workOrder->due_at,
            'url'           => route('work-orders.show', $this->workOrder),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type'          => 'work_order_assigned',
            'title'         => "New assignment: {$this->workOrder->code}",
            'message'       => $this->workOrder->title,
            'work_order_id' => $this->workOrder->id,
        ]);
    }
}

// ============================================================
// WorkOrderOverdue.php — Scheduled via Laravel Scheduler
// ============================================================

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\{Notification, Messages\MailMessage};

class WorkOrderOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WorkOrder $workOrder) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("⚠ OVERDUE: {$this->workOrder->code}")
            ->error()
            ->greeting("Action Required — {$notifiable->name}")
            ->line("Work order **{$this->workOrder->code}** is now overdue.")
            ->line("**Title:** {$this->workOrder->title}")
            ->line("**Was due:** {$this->workOrder->due_at?->format('M d, Y H:i')}")
            ->action('View & Update Now', route('work-orders.show', $this->workOrder));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'         => "OVERDUE: {$this->workOrder->code}",
            'message'       => $this->workOrder->title,
            'work_order_id' => $this->workOrder->id,
            'severity'      => 'critical',
        ];
    }
}
