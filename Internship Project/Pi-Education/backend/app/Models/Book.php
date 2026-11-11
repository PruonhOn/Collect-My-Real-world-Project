<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_FREE = 'FREE';

    const TYPE_PAID = 'PAID';

    const STATUS_DRAFT = 'DRAFT';

    const STATUS_PUBLISHED = 'PUBLISHED';

    const STATUS_ARCHIVED = 'ARCHIVED';

    protected $fillable = [
        'slug',
        'type',
        'price',
        'status',
        'cover_image',
        'language',
        'published_at',
        'reader_count',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'price' => 'decimal:2',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(BookTranslation::class);
    }

    public function translation(?string $locale = null): ?BookTranslation
    {
        $locale = $locale ?: app()->getLocale();

        $translation = $this->translations->firstWhere('locale', $locale);

        return $translation ?: $this->translations->first();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'book_category')->withPivot([]);
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'book_author')->withPivot([]);
    }

    public function purchasedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'book_purchase')->withPivot(['amount', 'payment_method', 'transaction_id']);
    }

    public function wishlistedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'book_wishlist')->withPivot([]);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', Review::STATUS_APPROVED);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function isFree(): bool
    {
        return $this->type === self::TYPE_FREE;
    }
}
