<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Server-generated free previews for gated books.
 *
 * A reader who has not paid (or has no active VIP membership) must never
 * receive the bytes of the full document, so the preview is built on the
 * server: the first `preview_pages` pages are imported with FPDI into a small
 * standalone PDF that is cached on the private book disk and streamed instead
 * of the original file. The artifact name is derived from the source file
 * identity and the preview length, so replacing the book or changing the
 * setting naturally points at a new artifact while old ones are pruned.
 */
class PreviewPdfService
{
    /**
     * Private path of the cached preview artifact for a book, relative to the
     * book's own file disk.
     */
    public function previewPath(Book $book): string
    {
        $identity = sha1(implode('|', [
            (string) $book->id,
            (string) $book->file_path,
            (string) $book->file_hash,
            (string) (int) $book->preview_pages,
        ]));

        return $this->previewDirectory().'/'.$book->id.'-'.substr($identity, 0, 20).'.pdf';
    }

    /**
     * Ensure the preview artifact exists and return its path on the book's
     * file disk. Generates it lazily on the first preview read.
     *
     * @throws HttpException when the source document is missing or unreadable
     */
    public function ensurePreview(Book $book): string
    {
        $disk = $book->fileStorageDisk();
        $fileSystem = Storage::disk($disk);
        $path = $this->previewPath($book);

        if ($fileSystem->exists($path)) {
            return $path;
        }

        if (! $book->file_path || ! $fileSystem->exists($book->file_path)) {
            abort(404, 'Book file not found.');
        }

        try {
            $bytes = $this->buildPreview($fileSystem, $book->file_path, max(1, (int) $book->preview_pages));
        } catch (\Throwable $exception) {
            report($exception);
            abort(500, 'The free preview could not be prepared.');
        }

        $fileSystem->put($path, $bytes);

        return $path;
    }

    /**
     * Drop every cached preview of this book that no longer matches its
     * current file or preview length. Best effort: a leftover artifact is
     * harmless, so failures are never surfaced to the caller.
     */
    public function prune(Book $book): void
    {
        $this->pruneMatching($book, $this->previewPath($book));
    }

    /**
     * Drop every cached preview of this book, regardless of its name.
     */
    public function pruneAll(Book $book): void
    {
        $this->pruneMatching($book, null);
    }

    private function pruneMatching(Book $book, ?string $keep): void
    {
        try {
            $fileSystem = Storage::disk($book->fileStorageDisk());

            foreach ($fileSystem->files($this->previewDirectory()) as $file) {
                if (str_starts_with(basename($file), $book->id.'-') && $file !== $keep) {
                    $fileSystem->delete($file);
                }
            }
        } catch (\Throwable) {
            // Best effort - stale artifacts are rebuilt/ignored anyway.
        }
    }

    private function previewDirectory(): string
    {
        return trim((string) config('books.file_directory'), '/').'/previews';
    }

    /**
     * Copy the first `$pages` pages of the source PDF into a new in-memory
     * PDF. Only the trimmed copy is ever handed to a preview-only reader.
     *
     * @throws \Throwable when the source cannot be read or parsed
     */
    private function buildPreview(Filesystem $fileSystem, string $path, int $pages): string
    {
        $source = $this->temporaryCopy($fileSystem, $path);

        try {
            $pdf = new Fpdi;
            $pageCount = (int) $pdf->setSourceFile($source);
            $limit = min($pages, max($pageCount, 1));

            for ($page = 1; $page <= $limit; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);
                $pdf->addPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template, 0, 0, $size['width'], $size['height']);
            }

            return $pdf->Output('S');
        } finally {
            @unlink($source);
        }
    }

    /**
     * Stream the stored document into a local temp file so FPDI can parse it.
     * Works for every disk (local or cloud) and guarantees the original file
     * is never touched.
     *
     * @throws \RuntimeException when the stored document cannot be read
     */
    private function temporaryCopy(Filesystem $fileSystem, string $path): string
    {
        $target = tempnam(sys_get_temp_dir(), 'book-preview-');

        if ($target === false) {
            throw new \RuntimeException('Unable to create a temporary file for preview generation.');
        }

        $source = $fileSystem->readStream($path);

        if (! is_resource($source)) {
            @unlink($target);

            throw new \RuntimeException("Unable to read the stored book file: {$path}");
        }

        $out = fopen($target, 'wb');

        if ($out === false) {
            fclose($source);
            @unlink($target);

            throw new \RuntimeException('Unable to open the temporary preview file.');
        }

        try {
            stream_copy_to_stream($source, $out);
        } finally {
            fclose($out);
            fclose($source);
        }

        return $target;
    }
}
