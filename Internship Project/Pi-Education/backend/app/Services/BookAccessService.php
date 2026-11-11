<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;

class BookAccessService
{
    public function canRead(?User $user, Book $book): bool
    {
        if ($book->isFree()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $user->isVipActive() || $user->ownedBooks()->whereKey($book->id)->exists();
    }

    public function canDownload(?User $user, Book $book): bool
    {
        if ($book->isFree()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $user->ownedBooks()->whereKey($book->id)->exists();
    }
}
