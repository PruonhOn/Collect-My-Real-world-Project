<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Purchase;
use App\Models\SharedAccess;
use App\Models\User;

class AccessService
{
    /**
     * Determine whether a user can read/access a book.
     * Access is granted via: paid purchase or active shared access.
     */
    public function hasAccess(User $user, ?int $bookId): bool
    {
        if (! $bookId) {
            return false;
        }

        $purchase = Purchase::where('user_id', $user->id)
            ->where('book_id', $bookId)
            ->where('status', Purchase::STATUS_PAID)
            ->exists();

        if ($purchase) {
            return true;
        }

        return SharedAccess::where('user_id', $user->id)
            ->where('book_id', $bookId)
            ->where('status', SharedAccess::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * The user has access only if they own the book or have active shared access.
     * Free books need no access; active VIP membership covers all paid books.
     */
    public function canRead(User $user, Book $book): bool
    {
        if ($book->is_free) {
            return true;
        }

        if (VipService::isActive($user)) {
            return true;
        }

        return $this->hasAccess($user, $book->id);
    }

    /**
     * Whether the user currently has an active VIP membership.
     */
    public function isVip(User $user): bool
    {
        return VipService::isActive($user);
    }

    /**
     * Books the user currently has lawful access to (owned + shared).
     */
    public function accessibleBookIds(User $user): array
    {
        $owned = Purchase::where('user_id', $user->id)
            ->where('status', Purchase::STATUS_PAID)
            ->whereNotNull('book_id')
            ->pluck('book_id');

        $shared = SharedAccess::where('user_id', $user->id)
            ->where('status', SharedAccess::STATUS_ACTIVE)
            ->pluck('book_id');

        return $owned->merge($shared)->unique()->all();
    }
}
