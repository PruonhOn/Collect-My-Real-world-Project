<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'description',
        'base_price',
        'cost_price',
        'brand',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryImage()
    {
        if ($this->relationLoaded('images')) {
            return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
        }

        return $this->images()->where('is_primary', true)->first() ?? $this->images->first();
    }

    public function orderItems()
    {
        return $this->hasManyThrough(OrderItem::class, ProductVariant::class, 'product_id', 'variant_id', 'id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getTotalStockAttribute(): int
    {
        return (int) $this->variants->sum('stock_quantity');
    }

    public function getVariantCountAttribute(): int
    {
        return $this->variants->count();
    }

    public function getHasVariantsAttribute(): bool
    {
        return $this->variants->isNotEmpty();
    }
}
