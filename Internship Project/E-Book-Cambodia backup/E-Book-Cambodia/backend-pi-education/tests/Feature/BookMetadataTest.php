<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BookMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_show_increments_view_count(): void
    {
        $book = Book::factory()->create(['view_count' => 5]);

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('book.view_count', 6);

        $this->assertDatabaseHas('books', ['id' => $book->id, 'view_count' => 6]);
    }

    public function test_admin_can_store_extended_book_metadata(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/books', [
                'category_id' => Category::factory()->create()->id,
                'title' => 'Advanced Study Guide',
                'author' => 'Sreymom',
                'publisher' => 'Pi Education',
                'subject' => 'Mathematics',
                'education_level' => 'Secondary',
                'grade' => 'Grade 9',
                'publication_date' => '2025-06-01',
                'is_vip_only' => true,
                'price' => 9.99,
                'is_free' => false,
                'is_published' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('book.publisher', 'Pi Education')
            ->assertJsonPath('book.subject', 'Mathematics')
            ->assertJsonPath('book.is_vip_only', true);
    }

    public function test_admin_store_auto_detects_pdf_page_count(): void
    {
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->createWithContent('book.pdf', $this->minimalPdf(4));

        $this->actingAs($admin, 'sanctum')
            ->post('/api/v1/admin/books', [
                'category_id' => Category::factory()->create()->id,
                'title' => 'Auto Pages Book',
                'file' => $file,
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('book.pages', 4);
    }

    public function test_admin_store_keeps_manual_pages_over_auto_detected(): void
    {
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->createWithContent('book.pdf', $this->minimalPdf(4));

        $this->actingAs($admin, 'sanctum')
            ->post('/api/v1/admin/books', [
                'category_id' => Category::factory()->create()->id,
                'title' => 'Manual Pages Book',
                'pages' => 99,
                'file' => $file,
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('book.pages', 99);
    }

    private function minimalPdf(int $pages): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count '.$pages.' >>',
        ];

        for ($i = 0; $i < $pages; $i++) {
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] >>';
        }

        $pdf = "%PDF-1.3\n";
        $offsets = [];

        foreach ($objects as $index => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$body."\nendobj\n";
        }

        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 ".($pages + 2)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= 'trailer'
            ."\n<< /Size ".($pages + 2)." /Root 1 0 R >>"
            ."\nstartxref\n".$xrefStart."\n%%EOF\n";

        return $pdf;
    }
}
