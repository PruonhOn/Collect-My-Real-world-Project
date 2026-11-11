<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'user_id',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'total',
        'amount_tendered',
        'change_due',
        'payment_method',
        'payment_status',
        'status',
        'refunded_amount',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_tendered' => 'decimal:2',
        'change_due' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'payment_method' => PaymentMethod::class,
        'status' => OrderStatus::class,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function getItemCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    public function isFullyRefunded(): bool
    {
        return $this->status === OrderStatus::Refunded;
    }

    public function getRemainingRefundableAttribute(): float
    {
        return max(0, (float) $this->total - (float) $this->refunded_amount);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', OrderStatus::Completed->value);
    }
}
