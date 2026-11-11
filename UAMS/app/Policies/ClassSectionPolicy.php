<?php

namespace App\Policies;

use App\Models\ClassSection;
use App\Models\User;

class ClassSectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin() || $user->isTeacher();
    }

    public function view(User $user, ClassSection $section): bool
    {
        return $user->isSuperAdmin()
            || $user->isAdmin()
            || ($user->teacher && $section->teacher_id === $user->teacher->id);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public function update(User $user, ClassSection $section): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }
}
