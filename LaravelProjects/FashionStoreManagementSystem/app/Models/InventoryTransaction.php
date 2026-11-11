<?php

namespace App\Models;

use App\Enums\InventoryTransactionType;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'variant_id',
        'user_id',
        'type',
        'direction',
        'quantity',
        'stock_before',
        'stock_after',
        'reason',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
        'type' => InventoryTransactionType::class,
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getReferenceUrlAttribute(): ?string
    {
        return match ($this->reference_type) {
            'order' => $this->reference_id ? route('orders.show', $this->reference_id) : null,
            default => null,
        };
    }
}
