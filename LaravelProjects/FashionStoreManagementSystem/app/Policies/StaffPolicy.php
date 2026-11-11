<?php

namespace App\Policies;

use App\Models\User;

class StaffPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $staff): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $staff): bool
    {
        return $user->isAdmin();
    }

    public function deactivate(User $user, User $staff): bool
    {
        return $user->isAdmin() && $staff->id !== $user->id;
    }

    public function delete(User $user, User $staff): bool
    {
        return $user->isAdmin() && $staff->id !== $user->id;
    }
}
