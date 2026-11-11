<?php

namespace App\Services;

use App\Models\Book;
use App\Models\ReadingProgress;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReadingProgressService
{
    /**
     * Attach each book's current-user reading progress as a `reading_progress`
     * attribute (last_page, total_pages, percent) so book list UIs can show
     * how far the reader has gone. No-op when there is no logged-in user.
     *
     * @param  Collection<int, Book>|LengthAwarePaginator  $books
     */
    public function attachTo($books, ?int $userId): void
    {
        if (! $userId) {
            return;
        }

        $collection = $books instanceof LengthAwarePaginator ? $books->getCollection() : $books;

        if ($collection->isEmpty()) {
            return;
        }

        $progress = ReadingProgress::query()
            ->where('user_id', $userId)
            ->whereIn('book_id', $collection->pluck('id'))
            ->get()
            ->keyBy('book_id');

        $collection->transform(function ($book) use ($progress) {
            $record = $progress->get($book->id);
            $book->setAttribute('reading_progress', $record ? [
                'last_page' => $record->last_page,
                'total_pages' => $record->total_pages,
                'percent' => (float) $record->progress,
            ] : null);

            return $book;
        });

        if ($books instanceof LengthAwarePaginator) {
            $books->setCollection($collection);
        }
    }
}
