<?php

namespace App\Models;

use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'admin_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Whether the given user is one of the two participants.
     */
    public function involves(User $user): bool
    {
        return $this->user_id === $user->id || $this->admin_id === $user->id;
    }

    /**
     * The other participant relative to a given participant.
     */
    public function otherParticipant(User $user): ?User
    {
        if ($this->user_id === $user->id) {
            return $this->admin;
        }

        if ($this->admin_id === $user->id) {
            return $this->user;
        }

        return null;
    }
}
