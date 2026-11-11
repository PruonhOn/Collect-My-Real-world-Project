<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Bookmark;
use App\Models\ReadingProgress;
use App\Models\User;
use App\Services\AccessService;
use App\Services\BookTocService;
use App\Services\PreviewPdfService;
use App\Services\SignedStreamService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReaderController extends Controller
{
    public function __construct(
        private AccessService $accessService,
        private SignedStreamService $signedStreamService,
        private PreviewPdfService $previewPdfService,
        private BookTocService $bookTocService,
    ) {}

    /**
     * Entry point for the reader. Returns book metadata + progress + bookmarks.
     * Free books are accessible without login; paid and VIP-only books require
     * authentication plus an access check. Visitors without full access fall
     * back to a free preview when the book offers one, in which case the
     * response is flagged with `access.mode = preview` and the text content is
     * cut down to the preview pages.
     */
    public function read(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->is_published, 404);
        abort_if($book->is_coming_soon, 403, 'This book is coming soon.');

        $user = auth('sanctum')->user();
        $scope = $this->accessService->readScope($user, $book);

        if ($scope === 'none') {
            if (! $user) {
                return response()->json(['message' => 'Please log in to access this book.'], 401);
            }

            return response()->json($this->deniedPayload($book), 403);
        }

        $progress = null;
        $bookmarks = collect();

        if ($user) {
            $progress = $user->readingProgress()->where('book_id', $book->id)->first();
            $bookmarks = $user->bookmarks()
                ->where('book_id', $book->id)
                ->orderBy('page')
                ->get();
        }

        $access = null;
        if ($book->file_path || $scope === 'preview') {
            $previewPages = $scope === 'preview' ? max(1, (int) $book->preview_pages) : null;
            $baseVersion = $book->contentVersion();
            $expires = now()->addSeconds(SignedStreamService::TTL_SECONDS)->getTimestamp();

            $access = [
                'can_read' => $scope === 'full',
                'mode' => $scope,
                'preview_pages' => $previewPages,
                'preview_mode' => $previewPages !== null ? $book->preview_mode : null,
                // Preview bytes differ from the full document, so they get a
                // distinct cache identity - a cached full file must never be
                // replayed to a preview-only reader (or the other way round).
                'version' => $previewPages !== null ? $baseVersion.'-p'.$previewPages : $baseVersion,
                'expires_at' => $expires,
                'stream_url' => $book->file_path
                    ? $this->signedStreamService->generate($book->id, $expires, $user?->id)
                    : null,
            ];
        }

        $payload = $book->load('category');
        $payload->setAttribute(
            'content',
            $this->accessService->visibleContent($book, $scope, (string) $payload->content)
        );

        return response()->json([
            'book' => $payload,
            'progress' => $progress,
            'bookmarks' => $bookmarks,
            'access' => $access,
            'toc' => $this->bookTocService->tree($book, $scope),
        ]);
    }

    /**
     * Protected stream/download of the book file. Free books are accessible
     * without login; paid and VIP-only books require authentication and lawful
     * access (valid purchase, shared access, or active VIP membership). Access is
     * always re-verified here on the backend - it is never trusted from the
     * client. Visitors without full access are served the server-generated
     * preview PDF instead of the full document. Supports short-lived signed
     * links and HTTP byte ranges so the reader never needs a permanent public
     * URL.
     */
    public function stream(Request $request, Book $book): StreamedResponse
    {
        abort_unless($book->is_published, 404);
        abort_if($book->is_coming_soon, 403, 'This book is coming soon.');

        $user = $this->resolveStreamUser($request, $book);
        $scope = $this->accessService->readScope($user, $book);

        if ($scope === 'none') {
            if (! $user) {
                abort(401, 'Please log in to access this book.');
            }

            abort(403, $this->deniedPayload($book)['message']);
        }

        $disk = Storage::disk($book->fileStorageDisk());

        if (! $book->file_path || ! $disk->exists($book->file_path)) {
            abort(404, 'Book file not found.');
        }

        $path = $scope === 'preview'
            ? $this->previewPdfService->ensurePreview($book)
            : $book->file_path;

        $size = (int) $disk->size($path);
        [$start, $end] = $this->parseByteRange($request->header('Range'), $size);

        return $this->streamPart($path, $book, $disk, $start, $end, $size, $start === null ? 200 : 206);
    }

    public function getProgress(Request $request, Book $book): JsonResponse
    {
        $this->assertBookAccess($request, $book);

        $progress = $request->user()->readingProgress()->where('book_id', $book->id)->first();

        return response()->json(['progress' => $progress]);
    }

    public function updateProgress(Request $request, Book $book): JsonResponse
    {
        $this->assertBookAccess($request, $book);

        $validated = $request->validate([
            'last_page' => ['required', 'integer', 'min:1'],
            'total_pages' => ['nullable', 'integer', 'min:0'],
        ]);

        $totalPages = $validated['total_pages'] ?? max($book->pages, $validated['last_page']);
        $progressPercent = $totalPages > 0
            ? round(($validated['last_page'] / $totalPages) * 100, 2)
            : 0;

        $progress = ReadingProgress::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'book_id' => $book->id,
            ],
            [
                'last_page' => $validated['last_page'],
                'total_pages' => $totalPages,
                'progress' => $progressPercent,
                'last_read_at' => now(),
            ]
        );

        return response()->json(['progress' => $progress]);
    }

    public function indexBookmarks(Request $request, Book $book): JsonResponse
    {
        $this->assertBookAccess($request, $book);

        $bookmarks = $request->user()->bookmarks()
            ->where('book_id', $book->id)
            ->orderBy('page')
            ->get();

        return response()->json(['bookmarks' => $bookmarks]);
    }

    public function storeBookmark(Request $request, Book $book): JsonResponse
    {
        $this->assertBookAccess($request, $book);

        $validated = $request->validate([
            'page' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $bookmark = Bookmark::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'book_id' => $book->id,
                'page' => $validated['page'],
            ],
            [
                'note' => $validated['note'] ?? null,
            ]
        );

        return response()->json(['bookmark' => $bookmark], 201);
    }

    public function destroyBookmark(Request $request, Bookmark $bookmark): JsonResponse
    {
        abort_unless($bookmark->user_id === $request->user()->id, 403);
        $bookmark->delete();

        return response()->json(['message' => 'Bookmark removed.']);
    }

    /**
     * Resolve the user behind a stream request. When signed query parameters
     * are present the short-lived link is verified; otherwise the current
     * Sanctum-authenticated user is used.
     */
    private function resolveStreamUser(Request $request, Book $book): ?User
    {
        if ($request->has('signature')) {
            return $this->signedStreamService->verifyRequest($request, $book);
        }

        return auth('sanctum')->user();
    }

    /**
     * Parse a HTTP `Range: bytes=` header into [start, end]. Returns
     * [null, null] for unsupported or unsatisfiable ranges so callers can
     * fall back to serving the full file.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function parseByteRange(?string $range, int $size): array
    {
        if (! $range || $size <= 0 || ! preg_match('/^bytes=(\d*)-(\d*)$/i', trim($range), $matches)) {
            return [null, null];
        }

        $startText = $matches[1];
        $endText = $matches[2];

        if ($startText === '' && $endText === '') {
            return [null, null];
        }

        if ($startText === '') {
            $suffix = (int) $endText;

            return $suffix > 0 ? [max(0, $size - $suffix), $size - 1] : [null, null];
        }

        $start = (int) $startText;
        $end = $endText === '' ? $size - 1 : min((int) $endText, $size - 1);

        if ($start >= $size || $end < $start) {
            return [null, null];
        }

        return [$start, $end];
    }

    private function streamPart(string $path, Book $book, Filesystem $disk, ?int $start, ?int $end, int $size, int $status): StreamedResponse
    {
        $headers = [
            'Content-Type' => $book->file_type ?: 'application/pdf',
            'Content-Length' => (string) ($start === null ? $size : ($end - $start + 1)),
            'Content-Disposition' => (new ResponseHeaderBag)->makeDisposition(
                ResponseHeaderBag::DISPOSITION_INLINE,
                $book->slug.'.pdf'
            ),
            'Cache-Control' => 'private, no-store',
            'Accept-Ranges' => 'bytes',
        ];

        if ($start !== null) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        return response()->stream(function () use ($path, $disk, $start, $end) {
            $stream = $disk->readStream($path);
            if (! is_resource($stream)) {
                return;
            }

            try {
                if ($start !== null) {
                    fseek($stream, $start);

                    $remaining = $end - $start + 1;
                    while ($remaining > 0 && ! feof($stream)) {
                        $chunk = fread($stream, 8192);
                        if ($chunk === false || $chunk === '') {
                            break;
                        }
                        $remaining -= strlen($chunk);
                        if ($remaining < 0) {
                            echo substr($chunk, 0, $remaining + strlen($chunk));
                        } else {
                            echo $chunk;
                        }
                    }
                } else {
                    while (! feof($stream)) {
                        $chunk = fread($stream, 8192);
                        if ($chunk === false || $chunk === '') {
                            break;
                        }
                        echo $chunk;
                    }
                }
            } finally {
                fclose($stream);
            }
        }, $status, $headers);
    }

    /**
     * Progress and bookmarks serve a reader who may open the document at all -
     * including a preview-only reader, since neither endpoint returns book
     * content. Only a viewer with no access at all is turned away.
     */
    private function assertBookAccess(Request $request, Book $book): void
    {
        abort_unless($book->is_published, 404);

        if ($this->accessService->readScope($request->user(), $book) === 'none') {
            abort(403, $this->deniedPayload($book)['message']);
        }
    }

    /**
     * Body for a denied read. `vip_required` lets the client tell "upgrade to
     * VIP" apart from "you never bought this" instead of pattern matching text.
     *
     * @return array{message: string, vip_required: bool}
     */
    private function deniedPayload(Book $book): array
    {
        if ($book->is_vip_only) {
            return [
                'message' => 'This book is available to VIP members only.',
                'vip_required' => true,
            ];
        }

        return [
            'message' => 'You do not have access to this book.',
            'vip_required' => false,
        ];
    }
}
