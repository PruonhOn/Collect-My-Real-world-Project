<?php

namespace App\Policies;

use App\Models\ProductVariant;
use App\Models\User;

class InventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function stockIn(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function adjust(User $user): bool
    {
        return $this->viewAny($user);
    }
}
