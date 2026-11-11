<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->isAdmin() || $announcement->created_by === $user->id;
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->isAdmin() || $announcement->created_by === $user->id;
    }
}