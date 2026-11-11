<?php

namespace App\Http\Controllers\Api;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Book;
use App\Models\Notification;
use App\Models\Purchase;
use App\Models\ReadingProgress;
use App\Models\Review;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function my(Request $request)
    {
        $user = $request->user();

        $bookmarks = Bookmark::where('user_id', $user->id)->count();

        $purchasedBooks = Purchase::where('user_id', $user->id)
            ->where('status', 'paid')
            ->with(['book'])
            ->get()
            ->map(function ($purchase) {
                $book = $purchase->book;

                return [
                    'id' => $book->id,
                    'slug' => $book->slug,
                    'title' => $book->title,
                    'cover_image' => $book->cover_image ? url('/storage/'.$book->cover_image) : null,
                    'is_free' => $book->is_free,
                    'pages' => $book->pages,
                ];
            });

        $readingProgress = ReadingProgress::where('user_id', $user->id)
            ->with(['book'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($progress) {
                $book = $progress->book;

                return [
                    'id' => $progress->id,
                    'progress' => $progress->progress,
                    'last_page' => $progress->last_page,
                    'total_pages' => $progress->total_pages,
                    'book' => [
                        'id' => $book->id,
                        'slug' => $book->slug,
                        'title' => $book->title,
                        'cover_image' => $book->cover_image ? url('/storage/'.$book->cover_image) : null,
                    ],
                ];
            });

        $reviews = Review::where('user_id', $user->id)->count();
        $unreadNotifications = Notification::where('user_id', $user->id)->unread()->count();

        return $this->success([
            'stats' => [
                'bookmarks' => $bookmarks,
                'reviews' => $reviews,
                'unread_notifications' => $unreadNotifications,
                'purchased_books' => $purchasedBooks->count(),
            ],
            'purchased_books' => $purchasedBooks,
            'reading_progress' => $readingProgress,
        ]);
    }
}
