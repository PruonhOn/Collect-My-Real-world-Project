<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A notification for an administrator or operator. Read by the Notifications
 * screen and marked as read through the update endpoint.
 */
class Notification extends Model
{
    use HasFactory;

    #[Fillable(['type', 'title', 'body', 'data', 'read_at'])]
    protected $guarded = [];

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * The entity this notification is for.
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Whether this notification has been read.
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
