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

    public function deleteBookFile(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }
}
