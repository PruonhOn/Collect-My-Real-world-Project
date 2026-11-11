<?php

namespace App\Listeners;

use App\Models\Book;
use App\Notifications\BookStatusChanged;

/**
 * Announces a reviewer's verdict on an author's book back to that author.
 *
 * Registered in AppServiceProvider against the model's own `updated` event
 * rather than each admin endpoint, so every path into the workflow -- review,
 * approve, reject, publish, unpublish, and any future alias -- notifies exactly
 * once.
 */
class NotifyAuthorOnBookStatusChange
{
    public function handle(Book $book): void
    {
        if (! $book->wasChanged('status')) {
            return;
        }

        $action = $this->action($book);

        if ($action === null) {
            return;
        }

        $author = $book->authorRelation()->first();

        if ($author === null) {
            return;
        }

        $author->notify(new BookStatusChanged(
            book: $book,
            action: $action,
            reviewNote: $book->review_note,
        ));
    }

    /**
     * Which verdict the author is being told about, or null when the change is
     * not theirs to hear about.
     *
     * Unpublishing transitions a book back to approved, so the previous status
     * is what separates "you were approved" from "you were taken down".
     */
    private function action(Book $book): ?string
    {
        return match ($book->status) {
            Book::STATUS_PUBLISHED => 'published',
            Book::STATUS_REJECTED => 'rejected',
            Book::STATUS_APPROVED => $book->getOriginal('status') === Book::STATUS_PUBLISHED
                ? 'unpublished'
                : 'approved',
            default => null,
        };
    }
}
