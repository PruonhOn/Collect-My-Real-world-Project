<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function markAllRead(Request $request)
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back();
    }
}
