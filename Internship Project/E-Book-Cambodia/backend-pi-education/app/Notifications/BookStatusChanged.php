<?php

namespace App\Notifications;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Tells an author that a reviewer acted on one of their books: approved,
 * rejected, published or pulled back off the storefront.
 *
 * The submission itself is announced to admins by BookSubmittedForReview; this
 * is the return leg, so an author finds out the verdict on their book without
 * having to keep reloading the review queue.
 */
class BookStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public Book $book,
        public string $action,
        public ?string $reviewNote = null,
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
            'action' => $this->action,
            'book_id' => $this->book->id,
            'book_title' => $this->book->title,
            'book_slug' => $this->book->slug,
            'book_cover' => $this->book->cover_image,
            'status' => $this->book->status,
            'review_note' => $this->reviewNote,
            'reviewed_at' => $this->book->reviewed_at,
            'published_at' => $this->book->published_at,
        ];
    }
}
