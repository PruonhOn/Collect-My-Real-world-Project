<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['title', 'slug', 'description', 'cover_image', 'price', 'is_published'])]
class Course extends Model
{
    use HasFactory;

    protected $casts = [
        'price' => 'decimal:2',
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Course $course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title);
            }
        });
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class);
    }
}
