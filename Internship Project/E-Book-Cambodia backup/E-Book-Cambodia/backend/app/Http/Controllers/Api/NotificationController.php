<?php

namespace App\Http\Controllers\Api;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return $this->success($notifications);
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->unread()
            ->count();

        return $this->success(['count' => $count]);
    }

    public function markRead(Request $request, int $id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $notification) {
            return $this->error('Notification not found', 404);
        }

        $notification->update(['read_at' => now()]);

        return $this->success($notification, 'Notification marked as read');
    }

    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return $this->success(null, 'All notifications marked as read');
    }
}
