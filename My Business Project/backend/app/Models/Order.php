<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id', 'customer_id', 'order_number', 'subtotal', 'tax', 'discount',
        'total', 'currency', 'status', 'payment_status', 'notes', 'metadata',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'tax' => 'integer',
        'discount' => 'integer',
        'total' => 'integer',
        'metadata' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BusinessInvoice::class);
    }
}
