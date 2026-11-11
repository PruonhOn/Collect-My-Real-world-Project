<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sharing_key_id', 'user_id', 'book_id', 'status', 'revoked_at'])]
class SharedAccess extends Model
{
    use HasFactory;

    protected $table = 'shared_access';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    protected $casts = [
        'revoked_at' => 'datetime',
    ];

    public function sharingKey(): BelongsTo
    {
        return $this->belongsTo(SharingKey::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
