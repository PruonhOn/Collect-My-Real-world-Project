<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\User;
use App\Services\AccessService;
use App\Services\Ai\AiAssistantService;
use App\Services\Ai\AiContextService;
use App\Services\Ai\AiProviderException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AiAssistantController extends Controller
{
    private const MAX_QUESTION_CHARS = 500;

    private const MAX_CONTEXT_CHARS = 60000;

    public function __construct(
        private AiAssistantService $assistant,
        private AiContextService $context,
        private AccessService $access,
    ) {}

    /**
     * GET /api/v1/ai/options
     */
    public function options()
    {
        return response()->json([
            'options' => $this->assistant->options(),
        ]);
    }

    /**
     * POST /api/v1/reader/{book}/ai
     */
    public function complete(Request $request, Book $book)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Authorization first, so an unauthorized caller learns nothing about
        // the endpoint's request shape.
        if (! $book->is_published) {
            return response()->json(['message' => 'Book is not published.'], 403);
        }

        // Preview-only readers may use the assistant inside their preview and
        // never on pages beyond it - the reference block would leak the rest
        // of the book.
        $scope = $this->access->readScope($user, $book);

        if ($scope === 'none') {
            return response()->json(['message' => 'You do not have access to this book.'], 403);
        }

        $validated = $request->validate([
            'action' => ['required', 'string', Rule::in($this->assistant->availableActions())],
            'page' => ['required', 'integer', 'min:1'],
            'language' => ['nullable', 'string', Rule::in($this->assistant->availableLanguages())],
            'query' => ['nullable', 'string', 'max:'.self::MAX_QUESTION_CHARS],
            'text' => ['nullable', 'string', 'max:'.self::MAX_CONTEXT_CHARS],
            'image' => ['nullable', 'string', 'max:'.self::maxImageDataUrlChars()],
        ]);

        $page = (int) $validated['page'];

        if ($scope === 'preview' && $page > max(1, (int) $book->preview_pages)) {
            return response()->json(['message' => 'You do not have access to this page.'], 403);
        }

        $action = (string) $validated['action'];
        $query = isset($validated['query']) ? Str::trim((string) $validated['query']) : '';

        // A page beyond the book length would be answered from an empty
        // reference block, so reject it rather than spend a provider call.
        $totalPages = (int) $book->pages;
        if ($totalPages > 0 && $page > $totalPages) {
            return response()->json(['message' => 'Page number exceeds the book length.'], 422);
        }

        if ($this->assistant->actionRequiresQuery($action) && $query === '') {
            return response()->json(['message' => 'A question is required for this action.'], 422);
        }

        // Build the context first so an unusable page fails before a provider
        // is ever contacted.
        $context = $this->context->build($book, $page, [
            'text' => $validated['text'] ?? null,
            'image' => $validated['image'] ?? null,
        ]);

        if (! $this->context->hasContext($context)) {
            return response()->json(['message' => 'No readable text was found on this page.'], 422);
        }

        if (! $this->assistant->isAvailable()) {
            return response()->json(['message' => 'The AI assistant is not available right now.'], 503);
        }

        try {
            $result = $this->assistant->run($user, $book, $page, [
                'action' => $action,
                'language' => $validated['language'] ?? null,
                'query' => $query,
                'text' => $validated['text'] ?? null,
                'image' => $validated['image'] ?? null,
            ]);
        } catch (AiProviderException $e) {
            // The provider's own message can name accounts, billing links or
            // gateway internals, so it goes to the log and the reader gets a
            // plain sentence they can act on.
            report($e);

            return response()->json([
                'message' => 'The AI assistant is temporarily unavailable. Please try again shortly.',
            ], 502);
        }

        return response()->json(['result' => $result]);
    }

    /**
     * Longest `data:` URL the image budget allows. base64 inflates by 4/3, plus
     * room for the mime type and the `data:...;base64,` prefix.
     */
    private static function maxImageDataUrlChars(): int
    {
        return (int) ceil(config('ai.max_image_bytes', 3 * 1024 * 1024) * 4 / 3) + 128;
    }
}
