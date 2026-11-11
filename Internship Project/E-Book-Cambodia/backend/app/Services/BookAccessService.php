<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;

class BookAccessService
{
    public function canAccess(User $user, Book $book): bool
    {
        if ($book->isFree()) {
            return true;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasPurchased($book);
    }
}
