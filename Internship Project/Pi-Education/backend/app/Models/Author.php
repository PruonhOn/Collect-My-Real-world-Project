<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Author extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['slug', 'image'];

    public function translations(): HasMany
    {
        return $this->hasMany(AuthorTranslation::class);
    }

    public function translation(?string $locale = null): ?AuthorTranslation
    {
        $locale = $locale ?: app()->getLocale();

        $translation = $this->translations->firstWhere('locale', $locale);

        return $translation ?: $this->translations->first();
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_author');
    }
}
