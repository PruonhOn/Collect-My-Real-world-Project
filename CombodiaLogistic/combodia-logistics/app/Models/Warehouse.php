<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'address',
        'city',
        'capacity',
        'used_capacity',
        'manager_name',
        'phone',
        'email',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'decimal:2',
            'used_capacity' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouseZones(): HasMany
    {
        return $this->hasMany(WarehouseZone::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function getAvailableCapacityAttribute(): float
    {
        return $this->capacity - $this->used_capacity;
    }
}
