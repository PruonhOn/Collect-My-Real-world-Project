<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'dispatch_id',
        'status',
        'location_name',
        'latitude',
        'longitude',
        'speed',
        'bearing',
        'recorded_at',
        'source',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'speed' => 'decimal:2',
            'bearing' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class);
    }
}
