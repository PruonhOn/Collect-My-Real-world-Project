<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title',
    'body',
    'type',
    'tone',
    'link_url',
    'link_label',
    'is_active',
    'starts_at',
    'ends_at',
    'created_by',
])]
class BroadcastMessage extends Model
{
    use HasFactory;

    /** Shown as a dismissible strip pinned to the top of the page. */
    public const TYPE_BANNER = 'banner';

    /** Shown as a centered dialog, once per visitor per message. */
    public const TYPE_POPUP = 'popup';

    public const TONE_INFO = 'info';

    public const TONE_SUCCESS = 'success';

    public const TONE_WARNING = 'warning';

    public const TONE_DANGER = 'danger';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Everything the public page is allowed to show: enabled, and inside its
     * optional scheduling window. Newest first, so the most recent
     * announcement leads.
     */
    public function scopeCurrentlyVisible(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->orderByDesc('id');
    }

    public function isPopup(): bool
    {
        return $this->type === self::TYPE_POPUP;
    }
}
