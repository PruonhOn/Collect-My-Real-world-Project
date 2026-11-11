<?php

namespace App\Policies;

use App\Models\ParentGuardian;
use App\Models\User;

class ParentGuardianPolicy
{
    public function view(User $user, ParentGuardian $parent): bool
    {
        return $user->isAdmin()
            || ($user->isParent() && $parent->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ParentGuardian $parent): bool
    {
        return $user->isAdmin()
            || ($user->isParent() && $parent->user_id === $user->id);
    }

    public function delete(User $user, ParentGuardian $parent): bool
    {
        return $user->isAdmin();
    }
}