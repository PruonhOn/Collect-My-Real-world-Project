<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'address',
        'city',
        'province',
        'phone',
        'email',
        'manager_name',
        'total_capacity',
        'used_capacity',
        'total_area',
        'is_active',
        'branch_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_capacity' => 'decimal:2',
            'used_capacity' => 'decimal:2',
            'total_area' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function warehouseLocations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }
}
