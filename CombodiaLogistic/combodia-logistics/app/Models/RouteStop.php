<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'stop_order',
        'estimated_arrival_time',
        'estimated_departure_time',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'stop_order' => 'integer',
            'estimated_arrival_time' => 'datetime',
            'estimated_departure_time' => 'datetime',
        ];
    }

    public function deliveryRoute(): BelongsTo
    {
        return $this->belongsTo(DeliveryRoute::class, 'route_id');
    }
}
