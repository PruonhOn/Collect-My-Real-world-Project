<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'type', 'price_monthly', 'price_yearly',
        'currency', 'is_active', 'sort_order', 'trial_enabled', 'trial_days', 'metadata',
    ];

    protected $casts = [
        'price_monthly' => 'integer',
        'price_yearly' => 'integer',
        'is_active' => 'boolean',
        'trial_enabled' => 'boolean',
        'sort_order' => 'integer',
        'trial_days' => 'integer',
        'metadata' => 'array',
    ];

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_feature')
            ->withPivot('enabled', 'limit_value', 'metadata')
            ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(BusinessSubscription::class);
    }
}
