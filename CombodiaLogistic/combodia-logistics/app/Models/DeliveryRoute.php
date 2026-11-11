<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryRoute extends Model
{
    use HasFactory;

    protected $table = 'routes';

    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'description',
        'start_location',
        'end_location',
        'distance',
        'estimated_duration',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'distance' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function routeStops(): HasMany
    {
        return $this->hasMany(RouteStop::class, 'route_id');
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class, 'route_id');
    }
}
