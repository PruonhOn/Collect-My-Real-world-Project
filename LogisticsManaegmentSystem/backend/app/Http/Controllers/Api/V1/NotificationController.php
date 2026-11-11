<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notifications for an actor: what they have been told and whether they have
 * read it yet.
 *
 * Read/update only: notifications are created by the system, never composed
 * through this screen. Marking one read is a PATCH that sets `read_at`.
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Notification::query()->orderByDesc('created_at');

        $unreadOnly = $request->boolean('unread_only', false);
        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate(
            max(1, min((int) $request->query('per_page', 25), 100)),
        );

        return ApiResponse::paginated(
            $notifications,
            'Notifications retrieved.',
            NotificationResource::collection($notifications->getCollection())->resolve(),
        );
    }

    public function show(Request $request, int $notification): JsonResponse
    {
        $record = Notification::query()->findOrFail($notification);

        return ApiResponse::success(new NotificationResource($record)->resolve(), 'Notification retrieved.');
    }

    /**
     * Mark a notification as read, or unread when `read` is false.
     */
    public function update(Request $request, int $notification): JsonResponse
    {
        $record = Notification::query()->findOrFail($notification);

        $validated = $request->validate([
            'read' => ['sometimes', 'boolean'],
        ]);

        $record->read_at = $validated['read'] ?? true ? now() : null;
        $record->save();

        return ApiResponse::success(
            new NotificationResource($record->fresh())->resolve(),
            'Notification updated.',
        );
    }
}
