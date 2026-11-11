<?php

namespace App\Traits;

use App\Models\Business;
use App\Scopes\BusinessScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBusiness
{
    protected static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope(new BusinessScope);

        static::creating(function ($model) {
            if (auth()->check() && ! isset($model->business_id)) {
                $business = app('current.business');
                if ($business) {
                    $model->business_id = $business->id;
                }
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeForBusiness(Builder $query, $businessId): Builder
    {
        return $query->withoutGlobalScope(BusinessScope::class)->where('business_id', $businessId);
    }
}
