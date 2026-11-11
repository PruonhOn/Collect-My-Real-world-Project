<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_id',
    'user_id',
    'status',
])]
class EventRegistration extends Model
{
    public const STATUS_REGISTERED = 'registered';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_ATTENDED = 'attended';

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
