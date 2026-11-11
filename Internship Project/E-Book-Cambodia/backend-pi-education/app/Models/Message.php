<?php

namespace App\Models;

use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'sender_id',
        'receiver_id',
        'sender_name',
        'sender_email',
        'photo',
        'subject',
        'topic',
        'message',
        'reply',
        'replied_at',
        'read_at',
        'admin_read_at',
        'from_admin',
    ];

    protected $appends = ['is_replied'];

    protected function casts(): array
    {
        return [
            'replied_at' => 'datetime',
            'read_at' => 'datetime',
            'admin_read_at' => 'datetime',
            'from_admin' => 'boolean',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReplied(): bool
    {
        return $this->reply !== null;
    }

    public function getIsRepliedAttribute(): bool
    {
        return $this->isReplied();
    }
}
