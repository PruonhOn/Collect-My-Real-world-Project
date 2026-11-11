<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function viewSales(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function viewInventory(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function export(User $user): bool
    {
        return $this->viewAny($user);
    }
}
