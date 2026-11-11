<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a private stored object through a short-lived signed URL.
 *
 * The signature proves the link was minted by the application, and generation
 * is only exposed behind authorized endpoints (e.g. the admin review preview),
 * so a non-admin can never craft one. Keeping this outside the admin middleware
 * lets the browser open the link in a new tab without a bearer token.
 */
class StorageController extends Controller
{
    public function serve(Request $request, string $disk, string $path): StreamedResponse
    {
        abort_unless($request->hasValidSignature(), 401, 'Invalid or expired link.');

        $filesystem = Storage::disk($disk);

        abort_if(! $filesystem->exists($path), 404, 'File not found.');

        $size = (int) $filesystem->size($path);
        $name = basename($path) ?: 'file';

        try {
            $mime = (string) $filesystem->mimeType($path);
        } catch (\Throwable) {
            $mime = 'application/octet-stream';
        }

        return response()->stream(function () use ($filesystem, $path) {
            $this->emit($filesystem, $path);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string) $size,
            'Content-Disposition' => (new ResponseHeaderBag)->makeDisposition(
                ResponseHeaderBag::DISPOSITION_INLINE,
                $name
            ),
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function emit(Filesystem $filesystem, string $path): void
    {
        $stream = $filesystem->readStream($path);

        if (! is_resource($stream)) {
            return;
        }

        try {
            while (! feof($stream)) {
                $chunk = fread($stream, 8192);

                if ($chunk === false || $chunk === '') {
                    break;
                }

                echo $chunk;
            }
        } finally {
            fclose($stream);
        }
    }
}
