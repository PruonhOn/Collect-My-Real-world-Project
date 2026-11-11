<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $fillable = ['slug', 'photo'];

    public function translations()
    {
        return $this->hasMany(AuthorTranslation::class);
    }

    public function translation(string $locale = 'km')
    {
        return $this->hasOne(AuthorTranslation::class)->where('locale', $locale);
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function getNameAttribute()
    {
        $locale = app()->getLocale();

        if ($this->relationLoaded('translations')) {
            $translation = $this->translations->firstWhere('locale', $locale)
                ?? $this->translations->firstWhere('locale', 'km');
        } else {
            $translation = $this->translation($locale)->first() ?? $this->translation('km')->first();
        }

        return $translation->name ?? $this->slug;
    }
}
