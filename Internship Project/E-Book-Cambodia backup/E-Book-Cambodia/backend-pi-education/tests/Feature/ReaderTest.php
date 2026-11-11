<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Bookmark;
use App\Models\Purchase;
use App\Models\ReadingProgress;
use App\Models\SharedAccess;
use App\Models\SharingKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // --- Access control ---

    public function test_free_book_is_readable_by_anyone(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->free()->create(['is_published' => true]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertOk()
            ->assertJsonPath('book.id', $book->id);
    }

    public function test_free_book_file_is_served_inline_for_preview(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('books/km/khmer-history.pdf', '%PDF-preview');

        $book = Book::factory()->free()->create([
            'file_path' => 'books/km/khmer-history.pdf',
            'file_type' => 'application/pdf',
        ]);

        $response = $this->get("/api/v1/reader/{$book->id}/stream");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename='.$book->slug.'.pdf');
        $response->assertStreamedContent('%PDF-preview');
    }

    public function test_paid_book_not_readable_without_purchase(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['price' => 5.00, 'is_free' => false, 'is_published' => true]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertStatus(403);
    }

    public function test_purchased_book_is_readable(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['price' => 5.00, 'is_free' => false, 'is_published' => true]);

        Purchase::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => 5.00,
            'status' => 'paid',
            'activated_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertOk()
            ->assertJsonPath('book.id', $book->id);
    }

    public function test_shared_access_book_is_readable(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $book = Book::factory()->create(['price' => 5.00, 'is_free' => false, 'is_published' => true]);

        $key = SharingKey::create([
            'owner_id' => $owner->id,
            'book_id' => $book->id,
            'code' => 'SHARED-READ-TEST',
            'max_uses' => 3,
        ]);

        SharedAccess::create([
            'sharing_key_id' => $key->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => 'active',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertOk();
    }

    public function test_revoked_shared_access_is_not_readable(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $book = Book::factory()->create(['price' => 5.00, 'is_free' => false, 'is_published' => true]);

        $key = SharingKey::create([
            'owner_id' => $owner->id,
            'book_id' => $book->id,
            'code' => 'SHARED-REV-TEST',
            'max_uses' => 3,
        ]);

        SharedAccess::create([
            'sharing_key_id' => $key->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => 'revoked',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertStatus(403);
    }

    public function test_reader_returns_bookmarks_and_progress(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->free()->create(['is_published' => true, 'pages' => 20]);

        Bookmark::create(['user_id' => $user->id, 'book_id' => $book->id, 'page' => 5]);
        Bookmark::create(['user_id' => $user->id, 'book_id' => $book->id, 'page' => 12]);
        ReadingProgress::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'last_page' => 12,
            'total_pages' => 20,
            'progress' => 60.00,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertOk()
            ->assertJsonCount(2, 'bookmarks')
            ->assertJsonPath('progress.progress', '60.00');
    }

    // --- Progress ---

    public function test_update_reading_progress(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->free()->create(['is_published' => true, 'pages' => 100]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/reader/{$book->id}/progress", [
                'last_page' => 30,
                'total_pages' => 100,
            ])
            ->assertOk()
            ->assertJsonPath('progress.last_page', 30)
            ->assertJsonPath('progress.progress', '30.00');

        $this->assertDatabaseHas('reading_progress', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'last_page' => 30,
            'progress' => 30.00,
        ]);
    }

    public function test_progress_updates_are_upserted(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->free()->create(['is_published' => true, 'pages' => 100]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/reader/{$book->id}/progress", [
                'last_page' => 10,
                'total_pages' => 100,
            ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/reader/{$book->id}/progress", [
                'last_page' => 50,
                'total_pages' => 100,
            ]);

        $this->assertDatabaseCount('reading_progress', 1);
        $this->assertDatabaseHas('reading_progress', ['last_page' => 50, 'progress' => 50.00]);
    }

    // --- Bookmarks ---

    public function test_add_and_list_bookmarks(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->free()->create(['is_published' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/reader/{$book->id}/bookmarks", [
                'page' => 5,
                'note' => 'Important chapter',
            ])
            ->assertStatus(201)
            ->assertJsonPath('bookmark.page', 5);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}/bookmarks")
            ->assertOk()
            ->assertJsonCount(1, 'bookmarks')
            ->assertJsonPath('bookmarks.0.note', 'Important chapter');
    }

    public function test_duplicate_bookmark_on_same_page_does_not_create_new(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->free()->create(['is_published' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/reader/{$book->id}/bookmarks", ['page' => 5])
            ->assertStatus(201);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/reader/{$book->id}/bookmarks", ['page' => 5])
            ->assertStatus(201);

        $this->assertDatabaseCount('bookmarks', 1);
    }

    public function test_delete_bookmark(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->free()->create(['is_published' => true]);
        $bookmark = Bookmark::create(['user_id' => $user->id, 'book_id' => $book->id, 'page' => 5]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/reader/bookmarks/{$bookmark->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Bookmark removed.');

        $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
    }

    public function test_user_cannot_delete_others_bookmark(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $book = Book::factory()->free()->create(['is_published' => true]);
        $bookmark = Bookmark::create(['user_id' => $user->id, 'book_id' => $book->id, 'page' => 5]);

        $this->actingAs($other, 'sanctum')
            ->deleteJson("/api/v1/reader/bookmarks/{$bookmark->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('bookmarks', ['id' => $bookmark->id]);
    }
}
