<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'shipment_id',
        'user_id',
        'request_number',
        'pickup_address',
        'pickup_city',
        'pickup_latitude',
        'pickup_longitude',
        'contact_name',
        'contact_phone',
        'scheduled_date',
        'scheduled_time',
        'status',
        'assigned_driver_id',
        'assigned_vehicle_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'pickup_latitude' => 'decimal:8',
            'pickup_longitude' => 'decimal:8',
            'scheduled_date' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'assigned_driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'assigned_vehicle_id');
    }
}
