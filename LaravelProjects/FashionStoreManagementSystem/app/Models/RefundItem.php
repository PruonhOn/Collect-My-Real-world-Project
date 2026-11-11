<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundItem extends Model
{
    protected $fillable = [
        'refund_id',
        'order_item_id',
        'variant_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function refund()
    {
        return $this->belongsTo(Refund::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
