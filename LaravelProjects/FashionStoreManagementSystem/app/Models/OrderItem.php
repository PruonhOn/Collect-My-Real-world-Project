<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'variant_id',
        'product_name',
        'variant_label',
        'sku',
        'unit_price',
        'cost_price',
        'quantity',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function refundItems()
    {
        return $this->hasMany(RefundItem::class);
    }

    public function getRefundedQuantityAttribute(): int
    {
        return (int) $this->refundItems->sum('quantity');
    }

    public function getRemainingQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->refunded_quantity);
    }
}
