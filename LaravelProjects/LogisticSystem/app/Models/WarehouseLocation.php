<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseLocation extends Model
{
    protected $fillable = [
        'code',
        'name',
        'zone',
        'aisle',
        'rack',
        'shelf',
        'bin',
        'max_weight',
        'max_volume',
        'is_occupied',
        'is_active',
        'warehouse_id',
    ];

    protected function casts(): array
    {
        return [
            'max_weight' => 'decimal:2',
            'max_volume' => 'decimal:2',
            'is_occupied' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'location_id');
    }

    public function stockMovementsFrom(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'from_location_id');
    }

    public function stockMovementsTo(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'to_location_id');
    }
}
