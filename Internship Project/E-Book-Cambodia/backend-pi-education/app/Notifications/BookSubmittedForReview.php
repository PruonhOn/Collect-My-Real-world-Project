<?php

namespace App\Notifications;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Tells an admin that an author has queued a book for review, so it shows up in
 * the review queue instead of waiting to be noticed.
 */
class BookSubmittedForReview extends Notification
{
    use Queueable;

    public function __construct(
        public Book $book,
        public int $authorId,
        public string $authorName,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'book_id' => $this->book->id,
            'book_title' => $this->book->title,
            'book_slug' => $this->book->slug,
            'book_cover' => $this->book->cover_image,
            'author_id' => $this->authorId,
            'author_name' => $this->authorName,
            'status' => $this->book->status,
        ];
    }
}
