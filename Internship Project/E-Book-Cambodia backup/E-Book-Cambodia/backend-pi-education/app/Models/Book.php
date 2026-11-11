<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id', 'title', 'slug', 'description', 'content', 'author', 'cover_image',
    'file_path', 'file_type', 'file_size', 'language', 'pages', 'price',
    'is_free', 'is_published', 'is_vip_only',
    'publisher', 'subject', 'education_level', 'grade', 'publication_date',
    'view_count', 'purchase_count',
])]
class Book extends Model
{
    use HasFactory;

    protected $casts = [
        'price' => 'decimal:2',
        'file_size' => 'integer',
        'pages' => 'integer',
        'is_free' => 'boolean',
        'is_published' => 'boolean',
        'is_vip_only' => 'boolean',
        'publication_date' => 'date',
        'view_count' => 'integer',
        'purchase_count' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function sharingKeys(): HasMany
    {
        return $this->hasMany(SharingKey::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', Review::STATUS_APPROVED);
    }

    public function allReviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_image
            ? asset('storage/'.$this->cover_image)
            : null;
    }
}
