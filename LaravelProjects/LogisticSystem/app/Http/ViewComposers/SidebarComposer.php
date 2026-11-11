<?php

namespace App\Http\ViewComposers;

use App\Models\Notification;
use App\Models\Shipment;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view): void
    {
        $pendingShipmentsCount = Shipment::where('status', 'pending')->count();

        $unreadNotificationsCount = 0;
        if (Auth::check()) {
            $user = Auth::user();
            $unreadNotificationsCount = Notification::where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->where('is_read', false)
                ->count();
        }

        $view->with('pendingShipmentsCount', $pendingShipmentsCount)
            ->with('unreadNotificationsCount', $unreadNotificationsCount);
    }
}
