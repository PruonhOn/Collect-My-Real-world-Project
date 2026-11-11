<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Delivery extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'delivery_number',
        'scheduled_date',
        'departed_at',
        'delivered_at',
        'status',
        'delivery_notes',
        'recipient_name',
        'recipient_phone',
        'recipient_signature',
        'proof_photo',
        'proof_document',
        'latitude',
        'longitude',
        'failure_reason',
        'shipment_id',
        'route_id',
        'driver_id',
        'vehicle_id',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'departed_at' => 'datetime',
            'delivered_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function deliveryStops(): HasMany
    {
        return $this->hasMany(DeliveryStop::class);
    }
}
