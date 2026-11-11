<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Single abstraction over where book documents and covers live.
 *
 * Every stored object is written to the disk configured in config/books.php
 * (driven by BOOKS_FILE_DISK / BOOKS_COVER_DISK) so the platform can move to
 * cloud storage without touching business code. Existing records keep their
 * original disk in file_disk / cover_disk so deletes and URLs always target
 * the disk the file actually lives on.
 */
class BookStorageService
{
    public function fileDisk(?string $disk = null): string
    {
        return $disk ?: (string) config('books.file_disk');
    }

    public function coverDisk(?string $disk = null): string
    {
        return $disk ?: (string) config('books.cover_disk');
    }

    /**
     * Reject anything that is not a genuine, non-empty PDF before it is
     * persisted. Checks magic bytes (a spoofed extension can pass the mimes
     * rule) and enforces a minimum size so empty/placeholder files never land
     * in the library.
     */
    public function validateDocument(UploadedFile $file): void
    {
        $size = (int) $file->getSize();
        $minBytes = (int) config('books.min_file_bytes');

        if ($size < $minBytes) {
            throw ValidationException::withMessages([
                'file' => "The file must be at least {$minBytes} bytes.",
            ]);
        }

        $handle = @fopen($file->getRealPath(), 'rb');
        $head = $handle === false ? '' : (string) fread($handle, 1024);

        if ($handle !== false) {
            fclose($handle);
        }

        if (! str_starts_with($head, '%PDF-')) {
            throw ValidationException::withMessages([
                'file' => 'The file must be a valid PDF document.',
            ]);
        }
    }

    /**
     * Store a new book document and return the full metadata set the books
     * table persists.
     *
     * @return array{file_disk: string, file_path: string, file_name: string, file_type: ?string, file_size: int, file_hash: string, pages: ?int}
     */
    public function storeDocument(UploadedFile $file, string $language, ?string $disk = null): array
    {
        $this->validateDocument($file);

        $disk = $this->fileDisk($disk);
        $hash = $this->hashFor($file);
        $directory = trim((string) config('books.file_directory'), '/').'/'.$language;

        $path = Storage::disk($disk)->putFileAs($directory, $file, $hash.'.pdf');

        return [
            'file_disk' => $disk,
            'file_path' => $this->normalizePath($path),
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType() ?: 'application/pdf',
            'file_size' => (int) $file->getSize(),
            'file_hash' => $hash,
            'pages' => $this->countPdfPages($file),
        ];
    }

    /**
     * Store a cover image and return its path on the cover disk. The stored
     * name is hashed so user-provided filenames can never collide or be used
     * for path traversal.
     */
    public function storeCover(UploadedFile $file, string $sub = 'covers', ?string $disk = null): string
    {
        $disk = $this->coverDisk($disk);
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
        $path = Storage::disk($disk)->putFileAs($sub, $file, $this->hashFor($file).'.'.$extension);

        return $this->normalizePath($path);
    }

    public function deleteDocument(?string $path, ?string $disk = null): void
    {
        if ($path) {
            Storage::disk($this->fileDisk($disk))->delete($path);
        }
    }

    public function deleteCover(?string $path, ?string $disk = null): void
    {
        if ($path) {
            Storage::disk($this->coverDisk($disk))->delete($path);
        }
    }

    public function documentExists(?string $path, ?string $disk = null): bool
    {
        return $path !== null
            && $path !== ''
            && Storage::disk($this->fileDisk($disk))->exists($path);
    }

    public function coverExists(?string $path, ?string $disk = null): bool
    {
        return $path !== null
            && $path !== ''
            && Storage::disk($this->coverDisk($disk))->exists($path);
    }

    /**
     * Public URL for a stored cover. The public disk is addressable directly;
     * private (cloud) disks fall back to a temporary signed URL.
     */
    public function coverUrl(?string $path, ?string $disk = null): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = $this->coverDisk($disk);

        if ($disk === 'public') {
            return Storage::disk($disk)->url($path);
        }

        $config = config("filesystems.disks.{$disk}");

        if (isset($config['url'])) {
            return Storage::disk($disk)->url($path);
        }

        return URL::temporarySignedRoute(
            'storage.serve',
            now()->addSeconds((int) config('books.signed_url_ttl_seconds')),
            ['disk' => $disk, 'path' => $path]
        );
    }

    /**
     * Temporary signed URL that lets a browser open a private book document
     * without a bearer token (admin review preview, share links, ...).
     */
    public function temporaryDocumentUrl(?string $path, ?string $disk = null, ?int $ttl = null): ?string
    {
        if (! $path) {
            return null;
        }

        return URL::temporarySignedRoute(
            'storage.serve',
            now()->addSeconds($ttl ?: (int) config('books.signed_url_ttl_seconds')),
            ['disk' => $this->fileDisk($disk), 'path' => $path]
        );
    }

    public function hashFor(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath()) ?: md5_file($file->getRealPath());
    }

    /**
     * Best-effort PDF page count read from the catalog's root Pages /Count.
     * Returns null when the PDF uses compressed object streams or the
     * structure cannot be read, so callers can keep the admin-provided value.
     */
    public function countPdfPages(UploadedFile $file): ?int
    {
        $content = @file_get_contents($file->getRealPath());

        if ($content === false) {
            return null;
        }

        if (! preg_match('/\/Root\s+(\d+)\s+\d+\s+R/', $content, $rootMatch)) {
            return null;
        }

        $rootId = (int) $rootMatch[1];

        if (! preg_match('/'.$rootId.'\s+0\s+obj(.*?)endobj/s', $content, $rootBlock)) {
            return null;
        }

        if (! preg_match('/\/Pages\s+(\d+)\s+\d+\s+R/', $rootBlock[1] ?? '', $pagesMatch)) {
            return null;
        }

        $pagesId = (int) $pagesMatch[1];

        if (! preg_match('/'.$pagesId.'\s+0\s+obj(.*?)endobj/s', $content, $pagesBlock)) {
            return null;
        }

        if (! preg_match('/\/Count\s+(\d+)/', $pagesBlock[1] ?? '', $countMatch)) {
            return null;
        }

        return (int) $countMatch[1];
    }

    private function normalizePath(string $path): string
    {
        if ($path === '' || $path === '/') {
            return $path;
        }

        return Str::replaceStart('/', '', $path);
    }
}
