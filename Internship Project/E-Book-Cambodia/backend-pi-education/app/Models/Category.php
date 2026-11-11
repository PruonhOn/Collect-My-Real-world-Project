<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['parent_id', 'name', 'slug', 'description', 'position'])]
class Category extends Model
{
    use HasFactory;

    public const SUPPORTED_LOCALES = ['en', 'km', 'zh', 'fr'];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('position')->orderBy('name');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    /**
     * Localized display name. Falls back to the legacy `name` column when
     * no translation exists for the requested locale.
     */
    public function translatedName(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        $translation = $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();

        return $translation?->name ?: (string) $this->name;
    }

    public function translatedDescription(?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();

        $translation = $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();

        return $translation?->description ?: $this->description;
    }

    public function isParent(): bool
    {
        return $this->children()->exists();
    }
}
