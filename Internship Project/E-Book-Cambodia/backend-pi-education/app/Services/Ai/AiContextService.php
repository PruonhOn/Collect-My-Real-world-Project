<?php

namespace App\Services\Ai;

use App\Models\Book;
use Illuminate\Support\Str;

/**
 * Normalizes the page context sent by the reader before it reaches a provider.
 *
 * Two sources are supported, in priority order:
 *
 *  1. The PDF text layer, which pdf.js extracts client side. Preferred because
 *     it is cheap, exact and searchable.
 *  2. A rendered image of the current page, which the provider reads with
 *     vision (OCR). This is the fallback that makes scanned PDFs work without
 *     the reader ever having to select text by hand.
 *
 * Security note: this service only ever handles text and image bytes the
 * client just rendered from the page the user is already viewing. It never
 * accepts a file path, storage key or URL, so the assistant cannot be used to
 * reach book files the caller is not authorized to read.
 */
class AiContextService
{
    /**
     * Below this many characters a text layer is treated as unusable (a scanned
     * page often yields a stray glyph or a page number), and the image is used
     * instead when one was provided.
     */
    private const MIN_USABLE_TEXT_CHARS = 24;

    /**
     * Image types the reader is allowed to submit. Deliberately a small allow
     * list: the bytes are decoded and re-encoded by the provider, so there is
     * no reason to accept arbitrary formats.
     *
     * @var array<int, string>
     */
    public const ALLOWED_IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Build the provider payload for a page context.
     *
     * @param  array{text?: string|null, image?: string|null}  $input
     * @return array{
     *     text: string,
     *     image_mime: string|null,
     *     image_base64: string|null,
     *     used_ocr: bool,
     *     source: string,
     *     truncated: bool
     * }
     */
    public function build(Book $book, int $page, array $input): array
    {
        $text = Str::of((string) ($input['text'] ?? ''))->replace("\u{0000}", '')->trim()->value();
        $image = $this->parseImageDataUrl($input['image'] ?? null);

        $hasUsableText = Str::length($text) >= self::MIN_USABLE_TEXT_CHARS;
        $usedOcr = $image !== null && ! $hasUsableText;

        if ($usedOcr) {
            $text = '';
        } elseif ($image !== null) {
            // A real text layer is present, so the image is redundant.
            $image = null;
        }

        $maxChars = (int) config('ai.max_text_chars', 12000);
        $truncated = false;
        if ($text !== '' && Str::length($text) > $maxChars) {
            $text = Str::limit($text, $maxChars, ' … [truncated]');
            $truncated = true;
        }

        return [
            'text' => $text,
            'image_mime' => $usedOcr ? $image['mime'] : null,
            'image_base64' => $usedOcr ? $image['base64'] : null,
            'used_ocr' => $usedOcr,
            'source' => $usedOcr ? 'ocr' : ($text !== '' ? 'text_layer' : 'none'),
            'truncated' => $truncated,
        ];
    }

    /**
     * Whether the page yielded any usable context at all. The controller turns
     * a false result into a 422 so the panel can explain the problem instead
     * of asking a model to guess.
     */
    public function hasContext(array $context): bool
    {
        return $context['text'] !== '' || $context['image_base64'] !== null;
    }

    /**
     * Decode a `data:` URL into its mime type and raw base64 payload.
     *
     * Returns null for anything that is not an allowed, in-budget image so the
     * same strictness applies to validation and to use.
     *
     * @return array{mime: string, base64: string}|null
     */
    public function parseImageDataUrl(?string $value): ?array
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        if (! preg_match('#^data:(image/[a-z0-9.+-]+);base64,(.+)$#is', trim($value), $matches)) {
            return null;
        }

        $mime = strtolower($matches[1]);
        if (! in_array($mime, self::ALLOWED_IMAGE_MIME_TYPES, true)) {
            return null;
        }

        $base64 = preg_replace('/\s+/', '', $matches[2]);
        if (! is_string($base64) || $base64 === '') {
            return null;
        }

        // Validate against the decoded size, since base64 inflates by ~4/3.
        $decodedSize = (int) (strlen($base64) * 3 / 4);
        if ($decodedSize > (int) config('ai.max_image_bytes', 3 * 1024 * 1024)) {
            return null;
        }

        return ['mime' => $mime, 'base64' => $base64];
    }
}
