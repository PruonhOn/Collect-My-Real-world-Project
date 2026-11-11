<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookTocItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookTocTest extends TestCase
{
    use RefreshDatabase;

    // --- Reader payload ---

    public function test_reader_receives_nested_toc(): void
    {
        $book = Book::factory()->free()->create(['is_published' => true]);

        $chapter = BookTocItem::factory()->for($book)->create([
            'title' => 'Chapter 1',
            'page' => 1,
            'position' => 0,
            'depth' => 1,
        ]);
        BookTocItem::factory()->for($book)->create([
            'parent_id' => $chapter->id,
            'title' => 'Section 1.1',
            'page' => 2,
            'position' => 1,
            'depth' => 2,
        ]);

        $this->getJson("/api/v1/reader/{$book->id}")
            ->assertOk()
            ->assertJsonPath('toc.0.title', 'Chapter 1')
            ->assertJsonPath('toc.0.locked', false)
            ->assertJsonPath('toc.0.children.0.title', 'Section 1.1');
    }

    public function test_preview_reader_gets_locked_toc_entries_past_preview_pages(): void
    {
        $book = Book::factory()->create([
            'price' => 5.00,
            'is_free' => false,
            'is_published' => true,
            'preview_pages' => 2,
            'preview_mode' => Book::PREVIEW_MODE_PREVIEW,
        ]);

        BookTocItem::factory()->for($book)->create(['title' => 'Chapter 1', 'page' => 1, 'position' => 0]);
        BookTocItem::factory()->for($book)->create(['title' => 'Chapter 2', 'page' => 5, 'position' => 1]);

        $this->getJson("/api/v1/reader/{$book->id}")
            ->assertOk()
            ->assertJsonPath('access.mode', 'preview')
            ->assertJsonPath('toc.0.locked', false)
            ->assertJsonPath('toc.1.locked', true);
    }

    // --- Admin management ---

    public function test_admin_can_replace_toc(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $book = Book::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/books/{$book->id}/toc", [
                'items' => [
                    ['title' => 'Cover', 'page' => 1],
                    ['title' => 'Chapter 1', 'page' => 3, 'parent_index' => 0],
                ],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'toc');

        $this->assertDatabaseHas('book_toc_items', [
            'book_id' => $book->id,
            'title' => 'Chapter 1',
            'depth' => 2,
        ]);
    }

    public function test_admin_can_list_and_clear_toc(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $book = Book::factory()->create();
        BookTocItem::factory()->for($book)->create(['title' => 'Intro']);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/books/{$book->id}/toc")
            ->assertOk()
            ->assertJsonPath('toc.0.title', 'Intro');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/books/{$book->id}/toc")
            ->assertOk();

        $this->assertDatabaseCount('book_toc_items', 0);
    }

    public function test_non_admin_cannot_manage_toc(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_READER]);
        $book = Book::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/admin/books/{$book->id}/toc")
            ->assertForbidden();
    }

    public function test_extract_reports_when_no_bookmarks_found(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('books/km/plain.pdf', "%PDF-1.7\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n%%EOF");

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $book = Book::factory()->create([
            'file_path' => 'books/km/plain.pdf',
            'file_disk' => 'local',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/books/{$book->id}/toc/extract")
            ->assertOk()
            ->assertJsonPath('extracted', 0);

        $this->assertDatabaseCount('book_toc_items', 0);
    }
}
