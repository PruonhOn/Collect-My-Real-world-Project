<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'warehouse_shelf_id',
        'product_name',
        'sku',
        'barcode',
        'description',
        'category',
        'unit',
        'quantity',
        'min_quantity',
        'max_quantity',
        'reorder_point',
        'unit_cost',
        'selling_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
            'reorder_point' => 'integer',
            'unit_cost' => 'decimal:2',
            'selling_price' => 'decimal:2',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseShelf(): BelongsTo
    {
        return $this->belongsTo(WarehouseShelf::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->quantity <= 0) {
            return 'out_of_stock';
        }

        if ($this->quantity <= $this->reorder_point) {
            return 'low_stock';
        }

        if ($this->quantity >= $this->max_quantity) {
            return 'overstocked';
        }

        return 'in_stock';
    }
}
