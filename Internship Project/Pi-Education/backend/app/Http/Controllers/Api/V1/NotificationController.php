<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'notifications' => NotificationResource::collection($notifications),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ], 'Notifications retrieved.');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->notifications()->unread()->count();

        return $this->success(['unread_count' => $count], 'Unread count retrieved.');
    }

    public function markRead(string $id, Request $request): JsonResponse
    {
        $notification = $request->user()->notifications()->find($id);

        if (! $notification) {
            return $this->error('Notification not found.', null, 404);
        }

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return $this->success(new NotificationResource($notification), 'Notification marked as read.');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->notifications()->unread()->update(['read_at' => now()]);

        return $this->success(null, 'All notifications marked as read.');
    }
}
