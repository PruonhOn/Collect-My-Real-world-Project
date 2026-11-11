<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['owner_id', 'book_id', 'code', 'max_uses', 'used_count', 'status', 'expires_at'])]
class SharingKey extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    public const MAX_USES = 3;

    protected $casts = [
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SharingKey $key) {
            if (empty($key->code)) {
                $key->code = Str::upper(Str::random(12));
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function sharedAccess(): HasMany
    {
        return $this->hasMany(SharedAccess::class);
    }

    public function isFull(): bool
    {
        return $this->used_count >= $this->max_uses;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && ! $this->isExpired()
            && ! $this->isFull();
    }

    public function isUsableBy(int $userId): bool
    {
        return $this->isActive() && $this->owner_id !== $userId;
    }
}
