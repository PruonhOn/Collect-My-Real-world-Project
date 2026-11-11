<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, MorphMany};
use Illuminate\Support\Str;

class WorkOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'title', 'description', 'type', 'priority', 'status',
        'asset_id', 'created_by', 'assigned_to', 'progress',
        'due_at', 'started_at', 'completed_at', 'resolution_notes',
    ];

    protected $casts = [
        'due_at'       => 'datetime',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'progress'     => 'integer',
    ];

    // ----------------------------------------------------------------
    // Boot — auto-generate WO code
    // ----------------------------------------------------------------
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (WorkOrder $wo) {
            $wo->code = 'WO-' . str_pad(
                (static::withTrashed()->count() + 1001),
                4, '0', STR_PAD_LEFT
            );
        });

        static::updated(function (WorkOrder $wo) {
            if ($wo->isDirty('status') || $wo->isDirty('progress')) {
                MaintenanceLog::create([
                    'work_order_id' => $wo->id,
                    'user_id'       => auth()->id() ?? $wo->created_by,
                    'action'        => $wo->isDirty('status') ? 'status_changed' : 'progress_updated',
                    'meta'          => [
                        'old_status' => $wo->getOriginal('status'),
                        'new_status' => $wo->status,
                        'progress'   => $wo->progress,
                    ],
                ]);
            }
        });
    }

    // ----------------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------------
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class)->latest();
    }

    public function alerts(): MorphMany
    {
        return $this->morphMany(Alert::class, 'alertable');
    }

    // ----------------------------------------------------------------
    // Scopes
    // ----------------------------------------------------------------
    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('status', 'open');
    }

    public function scopeOverdue(Builder $q): Builder
    {
        return $q->whereNotIn('status', ['completed', 'cancelled'])
                 ->where('due_at', '<', now());
    }

    public function scopeForTechnician(Builder $q, int $userId): Builder
    {
        return $q->where('assigned_to', $userId);
    }

    public function scopeHighPriority(Builder $q): Builder
    {
        return $q->whereIn('priority', ['high', 'critical']);
    }

    public function scopeFilter(Builder $q, array $filters): Builder
    {
        return $q
            ->when($filters['status']   ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['priority'] ?? null, fn($q, $v) => $q->where('priority', $v))
            ->when($filters['asset_id'] ?? null, fn($q, $v) => $q->where('asset_id', $v))
            ->when($filters['search']   ?? null, fn($q, $v) =>
                $q->where(fn($q) =>
                    $q->where('title', 'like', "%{$v}%")
                      ->orWhere('code', 'like', "%{$v}%")
                )
            );
    }

    // ----------------------------------------------------------------
    // Computed / Accessors
    // ----------------------------------------------------------------
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_at
            && $this->due_at->isPast()
            && !in_array($this->status, ['completed', 'cancelled']);
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'critical' => 'red',
            'high'     => 'orange',
            'medium'   => 'yellow',
            default    => 'green',
        };
    }

    // ----------------------------------------------------------------
    // Business logic
    // ----------------------------------------------------------------
    public function assign(User $technician): void
    {
        $this->update([
            'assigned_to' => $technician->id,
            'status'      => 'assigned',
        ]);

        $technician->notify(new \App\Notifications\WorkOrderAssigned($this));

        MaintenanceLog::create([
            'work_order_id' => $this->id,
            'user_id'       => auth()->id(),
            'action'        => 'assigned',
            'note'          => "Assigned to {$technician->name}",
        ]);
    }

    public function start(): void
    {
        $this->update(['status' => 'in_progress', 'started_at' => now()]);
    }

    public function complete(string $notes = ''): void
    {
        $this->update([
            'status'           => 'completed',
            'progress'         => 100,
            'completed_at'     => now(),
            'resolution_notes' => $notes,
        ]);
    }
}
