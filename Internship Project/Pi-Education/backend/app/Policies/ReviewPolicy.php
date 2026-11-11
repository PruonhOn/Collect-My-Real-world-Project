<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, ?Review $review): bool
    {
        return $review !== null && ($review->user_id === $user->id || $user->isAdmin());
    }

    public function delete(User $user, ?Review $review): bool
    {
        return $review !== null && ($review->user_id === $user->id || $user->isAdmin());
    }
}
