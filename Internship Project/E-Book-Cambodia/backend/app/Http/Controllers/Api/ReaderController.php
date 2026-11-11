<?php

namespace App\Http\Controllers\Api;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\ReadingProgress;
use Illuminate\Http\Request;

class ReaderController extends Controller
{
    use ApiResponse;

    public function saveProgress(Request $request, int $bookId)
    {
        $book = Book::where('id', $bookId)->where('is_published', true)->first();

        if (! $book) {
            return $this->error('Book not found', 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'last_page' => ['required', 'integer', 'min:0'],
            'total_pages' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $totalPages = $request->total_pages ?? $book->pages ?: max($request->last_page, 1);
        $progress = $totalPages > 0 ? round(($request->last_page / $totalPages) * 100, 2) : 0;

        $record = ReadingProgress::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'book_id' => $bookId,
            ],
            [
                'last_page' => $request->last_page,
                'total_pages' => $totalPages,
                'progress' => $progress,
                'last_read_at' => now(),
            ]
        );

        return $this->success($record, 'Progress saved');
    }

    public function getProgress(Request $request, int $bookId)
    {
        $progress = ReadingProgress::where('user_id', $request->user()->id)
            ->where('book_id', $bookId)
            ->first();

        if (! $progress) {
            return $this->success(null, 'No progress found');
        }

        return $this->success($progress);
    }
}
