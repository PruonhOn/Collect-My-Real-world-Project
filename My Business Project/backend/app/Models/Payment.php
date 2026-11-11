<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'business_subscription_id', 'payment_number', 'provider',
        'provider_payment_id', 'provider_transaction_id', 'amount', 'currency',
        'status', 'description', 'metadata', 'paid_at', 'refunded_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(BusinessSubscription::class, 'business_subscription_id');
    }

    public function invoice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Invoice::class);
    }
}
