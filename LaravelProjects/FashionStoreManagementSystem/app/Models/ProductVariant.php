<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'size',
        'color',
        'sku_variant',
        'barcode',
        'stock_quantity',
        'price_override',
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
        'price_override' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'variant_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    public function getLabelAttribute(): string
    {
        return collect([$this->color, $this->size])
            ->filter()
            ->map(fn ($v) => ucfirst($v))
            ->implode(' / ');
    }

    public function getPriceAttribute(): float
    {
        return $this->price_override !== null
            ? (float) $this->price_override
            : (float) $this->product->base_price;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= 5 && $this->stock_quantity > 0;
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->stock_quantity <= 0;
    }
}
