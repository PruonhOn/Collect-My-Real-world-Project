<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseApiController
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->notifications();

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $paginator = $this->paginate($query, $request, [], [], 'created_at', 'desc');

        return response()->json([
            'data' => NotificationResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'data' => ['unread_count' => $request->user()->notifications()->whereNull('read_at')->count()],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function show(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->getKey()) {
            abort(403, 'This notification belongs to another user.');
        }

        if ($notification->read_at === null) {
            $this->notifications->markRead($notification);
        }

        return response()->json([
            'data' => new NotificationResource($notification),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->getKey()) {
            abort(403, 'This notification belongs to another user.');
        }

        $this->notifications->markRead($notification);

        return response()->json([
            'data' => new NotificationResource($notification->refresh()),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->notifications->markAllRead($request->user());

        return response()->json([
            'data' => ['message' => 'All notifications marked as read.'],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->getKey()) {
            abort(403, 'This notification belongs to another user.');
        }

        $notification->delete();

        return response()->json([
            'data' => ['message' => 'Notification deleted.'],
            'meta' => null,
            'errors' => null,
        ]);
    }
}
