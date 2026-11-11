<?php

namespace Tests\Feature;

use App\Services\BookStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookStorageServiceTest extends TestCase
{
    private function service(): BookStorageService
    {
        return app(BookStorageService::class);
    }

    public function test_configured_disks_come_from_config(): void
    {
        $this->assertSame('local', $this->service()->fileDisk());
        $this->assertSame('public', $this->service()->coverDisk());
    }

    public function test_store_document_writes_file_and_returns_full_metadata(): void
    {
        Storage::fake('local');

        $content = $this->fakePdf(4);
        $meta = $this->service()->storeDocument(
            UploadedFile::fake()->createWithContent('book.pdf', $content),
            'km'
        );

        $this->assertSame('local', $meta['file_disk']);
        $this->assertSame('book.pdf', $meta['file_name']);
        $this->assertSame(strlen($content), $meta['file_size']);
        $this->assertSame(hash('sha256', $content), $meta['file_hash']);
        $this->assertSame(4, $meta['pages']);
        $this->assertSame('application/pdf', $meta['file_type']);
        $this->assertStringStartsWith('books/km/', $meta['file_path']);
        $this->assertStringEndsWith('.pdf', $meta['file_path']);

        Storage::disk('local')->assertExists($meta['file_path']);
    }

    public function test_store_document_uses_language_directory_but_never_user_filename(): void
    {
        Storage::fake('local');

        $meta = $this->service()->storeDocument(
            UploadedFile::fake()->createWithContent('../../../evil book.pdf', $this->fakePdf(3)),
            'en'
        );

        $this->assertStringStartsWith('books/en/', $meta['file_path']);
        $this->assertStringNotContainsString('../', $meta['file_path']);
        $this->assertStringContainsString($meta['file_hash'], $meta['file_path']);
    }

    public function test_store_document_rejects_non_pdf_content(): void
    {
        Storage::fake('local');

        $this->expectException(ValidationException::class);

        $this->service()->storeDocument(
            UploadedFile::fake()->createWithContent('book.pdf', str_repeat('A', 512)),
            'km'
        );
    }

    public function test_store_document_rejects_tiny_placeholder_files(): void
    {
        Storage::fake('local');

        $this->expectException(ValidationException::class);

        $this->service()->storeDocument(
            UploadedFile::fake()->createWithContent('book.pdf', "%PDF-1.3\n"),
            'km'
        );
    }

    public function test_store_and_delete_cover(): void
    {
        Storage::fake('public');

        $path = $this->service()->storeCover(UploadedFile::fake()->image('cover.jpg'));

        $this->assertMatchesRegularExpression('#^covers/.+\.jpg$#', $path);
        $this->assertStringNotContainsString('cover.jpg', $path);
        Storage::disk('public')->assertExists($path);

        $this->service()->deleteCover($path);

        Storage::disk('public')->assertMissing($path);
    }

    public function test_delete_uses_the_origin_disk_not_the_current_config(): void
    {
        Storage::fake('local');

        $path = 'books/km/abc.pdf';
        Storage::disk('local')->put($path, $this->fakePdf(4));

        $this->service()->deleteDocument($path, 'local');

        Storage::disk('local')->assertMissing($path);
    }

    public function test_cover_url_uses_disk_configuration(): void
    {
        Storage::fake('public');

        $path = $this->service()->storeCover(UploadedFile::fake()->image('cover.jpg'));

        $this->assertNotNull($this->service()->coverUrl($path, 'public'));
    }
}
