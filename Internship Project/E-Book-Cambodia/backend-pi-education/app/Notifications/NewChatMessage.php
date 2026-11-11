<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Announces an inbound message to an admin. Covers every source the admin has
 * to answer: a member's chat message, a guest contact-form submission and a
 * partnership enquiry. Admin-authored messages are never announced.
 */
class NewChatMessage extends Notification
{
    use Queueable;

    public function __construct(public Message $message) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender?->name ?? $this->message->sender_name,
            'sender_email' => $this->message->sender_email,
            'sender_avatar' => $this->message->sender?->avatar,
            'subject' => $this->message->subject,
            'source' => $this->source(),
            'message_excerpt' => Str::limit((string) $this->message->message, 120),
        ];
    }

    /**
     * Which inbox the message belongs in, so the admin lands on the right tab.
     * A conversation id means a member chat; otherwise the subject identifies
     * the form it arrived through, matching the labels adminIndex filters on.
     */
    public function source(): string
    {
        if ($this->message->conversation_id !== null) {
            return 'chat';
        }

        return $this->message->subject === 'Partnership / Business' ? 'collaboration' : 'contact';
    }
}
