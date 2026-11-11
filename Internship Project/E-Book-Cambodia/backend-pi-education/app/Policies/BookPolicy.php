<?php

namespace App\Policies;

use App\Models\Author;
use App\Models\Book;
use App\Models\User;

/**
 * Author-side authorization for books.
 *
 * Ownership is always checked first and answered with 404 by the controllers so
 * a book id belonging to another author is indistinguishable from one that
 * does not exist. This policy then decides the *action*: an author can only
 * ever move their own book between draft, pending and rejected - approving,
 * publishing and unpublishing are admin-only abilities with no author path.
 */
class BookPolicy
{
    /**
     * Reading a book is allowed for its owning author and for any admin.
     */
    public function view(Author|User $actor, Book $book): bool
    {
        return $actor instanceof User
            ? $actor->isAdmin()
            : $actor->ownsBook($book);
    }

    /**
     * Editing is limited to books the author owns that are not under review and
     * not yet live. A submitted or published book is frozen until an admin acts.
     */
    public function update(Author|User $actor, Book $book): bool
    {
        if ($actor instanceof User) {
            return $actor->isAdmin();
        }

        return $actor->ownsBook($book) && $book->isEditableByAuthor();
    }

    /**
     * Deleting follows the same rule as editing: only a draft or a rejected
     * book may be removed by its author, so a live title can never disappear.
     */
    public function delete(Author|User $actor, Book $book): bool
    {
        if ($actor instanceof User) {
            return $actor->isAdmin();
        }

        return $actor->ownsBook($book) && $book->isEditableByAuthor();
    }

    /**
     * Submitting for review is the furthest an author can advance a book. This
     * deliberately never returns true for a User: publication is not an
     * author-side ability under any circumstance.
     */
    public function submit(Author|User $actor, Book $book): bool
    {
        if ($actor instanceof User) {
            return false;
        }

        return $actor->ownsBook($book) && $book->isEditableByAuthor();
    }

    /**
     * Approving or rejecting a submission. Admin only.
     */
    public function review(User $actor, Book $book): bool
    {
        return $actor->isAdmin();
    }

    /**
     * Publishing or unpublishing an approved book. Admin only.
     */
    public function publish(User $actor, Book $book): bool
    {
        return $actor->isAdmin();
    }
}
