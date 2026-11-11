<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VipPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'price',
        'duration_days',
        'features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(VipPlanTranslation::class);
    }

    public function translation(?string $locale = null): ?VipPlanTranslation
    {
        $locale = $locale ?: app()->getLocale();

        $translation = $this->translations->firstWhere('locale', $locale);

        return $translation ?: $this->translations->first();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(VipSubscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
