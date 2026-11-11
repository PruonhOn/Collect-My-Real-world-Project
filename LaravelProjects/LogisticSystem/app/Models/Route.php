<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Route extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'origin_city',
        'destination_city',
        'distance_km',
        'estimated_hours',
        'description',
        'waypoints',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'estimated_hours' => 'decimal:2',
            'waypoints' => 'json',
            'is_active' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }
}
