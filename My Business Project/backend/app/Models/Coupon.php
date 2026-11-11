<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'min_amount', 'max_uses', 'uses_count',
        'starts_at', 'expires_at', 'is_active', 'metadata',
    ];

    protected $casts = [
        'value' => 'integer',
        'min_amount' => 'integer',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
        'starts_at' => 'date',
        'expires_at' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function isRedeemable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && now()->lt($this->starts_at->startOfDay())) {
            return false;
        }

        if ($this->expires_at && now()->gt($this->expires_at->endOfDay())) {
            return false;
        }

        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }
}
