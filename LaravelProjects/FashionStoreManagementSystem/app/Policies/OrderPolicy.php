<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->isManager() || $order->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function refund(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function delete(User $user, Order $order): bool
    {
        return false;
    }
}
