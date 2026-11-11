<?php

use Illuminate\Support\Facades\Broadcast;

// Private channel per user — for notifications
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel per work order — for updates
Broadcast::channel('work-order.{id}', function ($user, $id) {
    return $user->can('view work-orders');
});
