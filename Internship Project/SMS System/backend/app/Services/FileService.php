<?php

namespace App\Services;

use App\Models\File as FileRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

final class FileService
{
    /**
     * Store a validated upload and register it in the files table.
     *
     * @param  array<string>  $allowedMimes
     */
    public function storeUpload(UploadedFile $file, string $type, ?Model $fileable, array $allowedMimes, int $maxKb, ?int $uploadedBy = null): FileRecord
    {
        $actualMime = $file->getMimeType() ?? $file->getClientMimeType();

        if (! in_array($actualMime, $allowedMimes, true) || ! in_array($file->guessExtension(), $this->extensionsFor($allowedMimes), true)) {
            throw ValidationException::withMessages([
                'file' => 'The file type is not allowed.',
            ]);
        }

        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                'file' => sprintf('The file must not be larger than %d KB.', $maxKb),
            ]);
        }

        $path = $file->store($type, config('sms.storage_disk', config('filesystems.default')));

        if ($path === false) {
            throw new FileException('Unable to store the uploaded file.');
        }

        return FileRecord::create([
            'disk' => config('sms.storage_disk', config('filesystems.default')),
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $actualMime,
            'size' => $file->getSize(),
            'type' => $type,
            'uploaded_by' => $uploadedBy,
            'fileable_type' => $fileable ? $fileable->getMorphClass() : null,
            'fileable_id' => $fileable?->getKey(),
        ]);
    }

    /**
     * Persist generated content (e.g. PDF receipts, report exports).
     */
    public function storeContent(string $contents, string $originalName, string $mime, string $type, ?Model $fileable, ?int $uploadedBy = null): FileRecord
    {
        $diskConfig = config('sms.storage_disk', config('filesystems.default'));
        $dir = $type;

        $name = implode('_', [
            $type,
            now()->format('YmdHis'),
            bin2hex(random_bytes(4)),
            $originalName,
        ]);

        Storage::disk($diskConfig)->put($dir.'/'.$name, $contents, 'private');

        return FileRecord::create([
            'disk' => $diskConfig,
            'path' => $dir.'/'.$name,
            'original_name' => $originalName,
            'mime_type' => $mime,
            'size' => strlen($contents),
            'type' => $type,
            'uploaded_by' => $uploadedBy,
            'fileable_type' => $fileable ? $fileable->getMorphClass() : null,
            'fileable_id' => $fileable?->getKey(),
        ]);
    }

    public function url(FileRecord $file): string
    {
        return Storage::disk($file->disk)->url($file->path);
    }

    public function download(FileRecord $file, ?string $name = null): BinaryFileResponse
    {
        $path = Storage::disk($file->disk)->path($file->path);

        if (! is_file($path)) {
            throw new FileException('The stored file is missing.');
        }

        return response()->file($path, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => 'attachment; filename="'.($name ?? $file->original_name).'"',
        ]);
    }

    /**
     * @param  array<string>  $mimes
     * @return array<string>
     */
    private function extensionsFor(array $mimes): array
    {
        return collect($mimes)
            ->map(fn (string $mime) => match ($mime) {
                'image/jpeg' => 'jpeg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'application/pdf' => 'pdf',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'application/vnd.ms-excel' => 'xls',
                'text/csv' => 'csv',
                default => null,
            })
            ->filter()
            ->all();
    }
}
