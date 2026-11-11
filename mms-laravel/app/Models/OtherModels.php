<?php
// ============================================================
// app/Models/Asset.php
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'category', 'location', 'status',
        'installed_at', 'warranty_expires_at', 'meta',
    ];

    protected $casts = [
        'installed_at'       => 'date',
        'warranty_expires_at'=> 'date',
        'meta'               => 'array',
    ];

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function alerts(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Alert::class, 'alertable');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    public function scopeByCategory(Builder $q, string $cat): Builder
    {
        return $q->where('category', $cat);
    }

    public function getOpenWorkOrdersCountAttribute(): int
    {
        return $this->workOrders()->whereNotIn('status', ['completed', 'cancelled'])->count();
    }

    public function getIsWarrantyActiveAttribute(): bool
    {
        return $this->warranty_expires_at && $this->warranty_expires_at->isFuture();
    }
}


// ============================================================
// app/Models/MaintenanceLog.php
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo};

class MaintenanceLog extends Model
{
    protected $fillable = ['work_order_id', 'user_id', 'action', 'note', 'meta'];

    protected $casts = ['meta' => 'array'];

    public function workOrder(): BelongsTo { return $this->belongsTo(WorkOrder::class); }
    public function user(): BelongsTo      { return $this->belongsTo(User::class); }

    public function getActionLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->action));
    }
}


// ============================================================
// app/Models/Alert.php
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, MorphTo};

class Alert extends Model
{
    protected $fillable = ['title', 'message', 'severity', 'alertable_type', 'alertable_id', 'triggered_by', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function alertable(): MorphTo { return $this->morphTo(); }
    public function triggeredBy(): BelongsTo { return $this->belongsTo(User::class, 'triggered_by'); }

    public function scopeUnread($q)  { return $q->whereNull('read_at'); }
    public function scopeCritical($q){ return $q->where('severity', 'critical'); }

    public function markAsRead(): void { $this->update(['read_at' => now()]); }

    public function getIsReadAttribute(): bool { return !is_null($this->read_at); }
}
