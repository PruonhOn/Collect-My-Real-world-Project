<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Book extends Model
{
    protected $fillable = [
        'slug', 'category_id', 'author_id', 'cover_image', 'file_path',
        'file_type', 'file_size', 'pages', 'price', 'is_free',
        'is_published', 'views_count', 'downloads_count',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_free' => 'boolean',
            'is_published' => 'boolean',
            'file_size' => 'integer',
            'pages' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(BookTranslation::class);
    }

    public function translation(string $locale = 'km'): HasOne
    {
        return $this->hasOne(BookTranslation::class)->where('locale', $locale);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function isFree(): bool
    {
        return (bool) $this->is_free;
    }

    public function getTitleAttribute()
    {
        $translation = $this->resolvedTranslation();

        return $translation->title ?? $this->slug;
    }

    public function getDescriptionAttribute()
    {
        $translation = $this->resolvedTranslation();

        return $translation->description ?? null;
    }

    public function getContentAttribute()
    {
        $translation = $this->resolvedTranslation();

        return $translation->content ?? null;
    }

    protected function resolvedTranslation()
    {
        $locale = app()->getLocale();

        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale)
                ?? $this->translations->firstWhere('locale', 'km');
        }

        return $this->translation($locale)->first() ?? $this->translation('km')->first();
    }

    public function averageRating(): ?float
    {
        return round($this->reviews()->avg('rating'), 1);
    }

    public function reviewsCount(): int
    {
        return $this->reviews()->count();
    }
}
