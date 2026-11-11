<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    protected $fillable = ['slug', 'status'];

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function translation(?string $locale = null): ?CategoryTranslation
    {
        $locale = $locale ?: app()->getLocale();

        $translation = $this->translations->firstWhere('locale', $locale);

        return $translation ?: $this->translations->first();
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class);
    }
}
