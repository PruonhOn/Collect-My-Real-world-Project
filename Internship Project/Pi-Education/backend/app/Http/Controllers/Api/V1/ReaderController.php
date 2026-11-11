<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReadingProgressResource;
use App\Models\Book;
use App\Models\ReadingProgress;
use App\Services\BookAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReaderController extends Controller
{
    public function __construct(private readonly BookAccessService $access) {}

    public function content(string $slug, Request $request): JsonResponse
    {
        $book = $this->findBook($slug);

        $user = $request->user('sanctum');

        if (! $this->access->canRead($user, $book)) {
            return $this->error('You do not have access to this book.', null, 403);
        }

        $progress = $user
            ? ReadingProgress::where('user_id', $user->id)->where('book_id', $book->id)->first()
            : null;

        $locale = $request->header('X-Locale', app()->getLocale());

        $translation = $book->translation($locale);

        return $this->success([
            'book' => [
                'id' => $book->id,
                'slug' => $book->slug,
                'title' => $translation?->title,
                'type' => $book->type,
                'language' => $book->language,
            ],
            'content' => $translation?->content,
            'progress' => $progress ? (int) $progress->progress : 0,
        ], 'Book content retrieved.');
    }

    public function myBooks(Request $request): JsonResponse
    {
        $user = $request->user();

        $free = Book::query()
            ->published()
            ->where('type', Book::TYPE_FREE)
            ->with(['translations'])
            ->get();

        $owned = $user->ownedBooks()
            ->whereNull('deleted_at')
            ->with(['translations'])
            ->get();

        $books = $free->merge($owned)->unique('id')->values();

        $progress = $user->readingProgress()
            ->whereIn('book_id', $books->pluck('id'))
            ->get()
            ->keyBy('book_id');

        return $this->success([
            'books' => [
                'data' => $books->map(function (Book $book) use ($progress) {
                    $translation = $book->translation();
                    $record = $progress->get($book->id);

                    return [
                        'id' => $book->id,
                        'slug' => $book->slug,
                        'title' => $translation?->title,
                        'type' => $book->type,
                        'cover_image' => $book->cover_image,
                        'progress' => $record ? (int) $record->progress : 0,
                    ];
                })->values(),
            ],
        ], 'My books retrieved.');
    }

    public function progress(string $slug, Request $request): JsonResponse
    {
        $book = $this->findBook($slug);

        $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        if (! $this->access->canRead($request->user(), $book)) {
            return $this->error('You do not have access to this book.', null, 403);
        }

        $progress = ReadingProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'book_id' => $book->id],
            [
                'progress' => $request->integer('progress'),
                'last_read_at' => now(),
            ]
        );

        return $this->success(new ReadingProgressResource($progress), 'Progress saved.');
    }

    private function findBook(string $slug): Book
    {
        $book = Book::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        abort_unless($book, 404);

        return $book;
    }
}
