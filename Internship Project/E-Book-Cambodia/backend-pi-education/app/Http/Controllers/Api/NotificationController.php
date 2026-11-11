<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Notifications\BookStatusChanged;
use App\Notifications\BookSubmittedForReview;
use App\Notifications\NewChatMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * The bell behind every portal. Members, admins and authors each have their own
 * route into these three actions, but the notifiable is always whatever the
 * caller's token resolved to, so no endpoint can reach another account's feed.
 */
class NotificationController extends Controller
{
    /**
     * List the authenticated account's notifications, newest first.
     */
    public function index(Request $request): JsonResponse
    {
        $notifiable = $request->user();

        $notifications = $notifiable->notifications()
            ->latest()
            ->take(50)
            ->get()
            ->map(fn (DatabaseNotification $notification) => $this->payload($notification));

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $notifiable->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a single notification as read. Scoped to the authenticated account.
     */
    public function markRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        $notifiable = $request->user();

        abort_unless($this->belongsTo($notification, $notifiable), 403, 'This notification does not belong to you.');

        $notification->markAsRead();

        return response()->json([
            'id' => $notification->id,
            'read' => true,
            'unread_count' => $notifiable->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark every one of the authenticated account's notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }

    private function belongsTo(DatabaseNotification $notification, Model $notifiable): bool
    {
        return $notification->notifiable_id === $notifiable->getAuthIdentifier()
            && $notification->notifiable_type === $notifiable->getMorphClass();
    }

    private function payload(DatabaseNotification $notification): array
    {
        $type = match ($notification->type) {
            NewChatMessage::class => 'new_message',
            BookSubmittedForReview::class => 'book_uploaded',
            BookStatusChanged::class => 'book_status_changed',
            default => str_replace('App\\Notifications\\', '', $notification->type),
        };

        return [
            'id' => (string) $notification->id,
            'type' => $type,
            'data' => $notification->data,
            'created_at' => $notification->created_at,
            'read' => $notification->read_at !== null,
        ];
    }
}
