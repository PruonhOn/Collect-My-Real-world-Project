<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    protected $fillable = ['slug', 'icon', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function translations()
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function translation(string $locale = 'km')
    {
        return $this->hasOne(CategoryTranslation::class)->where('locale', $locale);
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function getNameAttribute()
    {
        $translation = $this->resolvedTranslation();

        return $translation->name ?? $this->slug;
    }

    public function getDescriptionAttribute()
    {
        $translation = $this->resolvedTranslation();

        return $translation->description ?? null;
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

    public function categoryTranslation(): BelongsTo
    {
        return $this->belongsTo(CategoryTranslation::class, 'id', 'category_id');
    }
}
