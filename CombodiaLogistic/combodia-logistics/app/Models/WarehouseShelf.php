<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseShelf extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_zone_id',
        'code',
        'name',
        'max_weight',
        'current_weight',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_weight' => 'decimal:2',
            'current_weight' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function warehouseZone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }
}
