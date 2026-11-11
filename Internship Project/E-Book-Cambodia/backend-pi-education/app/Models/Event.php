<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'description',
    'cover_image',
    'category',
    'start_date',
    'end_date',
    'location',
    'organizer',
    'registration_link',
    'contact_phone',
    'contact_email',
    'max_participants',
    'fee_type',
    'fee_amount',
    'speaker',
    'social_links',
    'status',
    'is_featured',
])]
class Event extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    public const FEE_FREE = 'free';

    public const FEE_PAID = 'paid';

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'max_participants' => 'integer',
            'fee_amount' => 'decimal:2',
            'social_links' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Remaining spots, or null when there is no participant cap.
     */
    public function spotsLeft(): ?int
    {
        if ($this->max_participants === null) {
            return null;
        }

        $registered = $this->relationLoaded('registrations')
            ? $this->registrations->count()
            : ($this->registrations_count ?? $this->registrations()->count());

        return max(0, $this->max_participants - $registered);
    }

    public function isFull(): bool
    {
        return $this->max_participants !== null && $this->spotsLeft() <= 0;
    }
}
