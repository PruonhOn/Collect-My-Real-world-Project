<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'category',
        'quantity',
        'min_quantity',
        'max_quantity',
        'unit',
        'weight_per_unit',
        'volume_per_unit',
        'unit_price',
        'barcode',
        'qr_code',
        'is_active',
        'warehouse_id',
        'location_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'min_quantity' => 'decimal:2',
            'max_quantity' => 'decimal:2',
            'weight_per_unit' => 'decimal:2',
            'volume_per_unit' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
