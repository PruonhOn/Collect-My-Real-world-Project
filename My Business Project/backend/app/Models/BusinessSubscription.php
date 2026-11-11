<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'plan_id', 'status', 'billing_cycle', 'provider',
        'provider_subscription_id', 'provider_customer_id',
        'trial_starts_at', 'trial_ends_at', 'starts_at', 'ends_at',
        'canceled_at', 'grace_ends_at', 'cancel_at_period_end', 'metadata',
    ];

    protected $casts = [
        'trial_starts_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'grace_ends_at' => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'metadata' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
