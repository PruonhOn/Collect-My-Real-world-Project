<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'route_id',
        'dispatch_number',
        'status',
        'scheduled_date',
        'scheduled_time',
        'dispatched_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'datetime',
            'dispatched_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function deliveryRoute(): BelongsTo
    {
        return $this->belongsTo(DeliveryRoute::class, 'route_id');
    }

    public function dispatchDrivers(): HasMany
    {
        return $this->hasMany(DispatchDriver::class);
    }

    public function trackingHistories(): HasMany
    {
        return $this->hasMany(TrackingHistory::class);
    }
}
