<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'description'])]
class Category extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if ($category->slug === null) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
