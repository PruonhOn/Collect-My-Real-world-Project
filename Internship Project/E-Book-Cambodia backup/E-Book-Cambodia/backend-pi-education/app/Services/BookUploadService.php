<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BookUploadService
{
    public function storeCover(UploadedFile $file, string $sub = 'covers'): string
    {
        return $file->store($sub, 'public');
    }

    public function deleteCover(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    public function storeBookFile(UploadedFile $file, string $language): array
    {
        $path = $file->store('books/'.$language, 'local');

        return [
            'file_path' => $path,
            'file_type' => $file->getMimeType() ?: 'application/pdf',
            'file_size' => $file->getSize(),
        ];
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

    public function deleteBookFile(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }
}
