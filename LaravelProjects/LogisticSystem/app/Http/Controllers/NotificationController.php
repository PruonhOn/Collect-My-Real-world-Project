<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function index(Request $request): View
    {
        $notifications = $this->notificationService->getAll(auth()->id(), $request->only(['is_read']));
        $unreadCount = $this->notificationService->getUnreadCount(auth()->id());
        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markAsRead(string $id): RedirectResponse
    {
        try {
            $this->notificationService->markAsRead($id, auth()->id());
            return back()->with('success', 'Notification marked as read.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to mark notification: ' . $e->getMessage());
        }
    }

    public function markAllAsRead(): RedirectResponse
    {
        try {
            $this->notificationService->markAllAsRead(auth()->id());
            return back()->with('success', 'All notifications marked as read.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to mark notifications: ' . $e->getMessage());
        }
    }
}
