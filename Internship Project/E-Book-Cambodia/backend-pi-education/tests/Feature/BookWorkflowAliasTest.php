<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\User;
use App\Services\BookStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookWorkflowAliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_an_approved_book_via_book_resource_alias(): void
    {
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->approved()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/books/{$book->id}/publish")
            ->assertOk()
            ->assertJsonPath('book.status', Book::STATUS_PUBLISHED);

        $this->assertTrue($book->fresh()->is_published);
    }

    public function test_admin_can_unpublish_a_live_book_via_book_resource_alias(): void
    {
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->published()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/books/{$book->id}/unpublish")
            ->assertOk()
            ->assertJsonPath('book.status', Book::STATUS_APPROVED);

        $this->assertFalse($book->fresh()->is_published);
    }

    public function test_admin_can_approve_a_submission_via_book_resource_alias(): void
    {
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->pending()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/books/{$book->id}/approve")
            ->assertOk()
            ->assertJsonPath('book.status', Book::STATUS_APPROVED);
    }

    public function test_admin_can_reject_a_submission_via_book_resource_alias_with_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->pending()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/books/{$book->id}/reject", ['review_note' => 'Cover is pixelated.'])
            ->assertOk()
            ->assertJsonPath('book.status', Book::STATUS_REJECTED)
            ->assertJsonPath('book.review_note', 'Cover is pixelated.');
    }

    public function test_admin_cannot_publish_a_book_that_is_not_approved(): void
    {
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->draft()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/books/{$book->id}/publish")
            ->assertStatus(409);
    }

    public function test_author_can_submit_via_submit_review_alias(): void
    {
        $author = Author::factory()->create();
        $book = Book::factory()->draft()->create(['author_id' => $author->id]);

        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/author/books/{$book->id}/submit-review")
            ->assertOk()
            ->assertJsonPath('book.status', Book::STATUS_PENDING);
    }

    public function test_public_access_endpoint_reports_read_access_for_guest(): void
    {
        $free = Book::factory()->free()->published()->create();
        $paid = Book::factory()->published()->create(['is_free' => false]);

        $this->getJson("/api/v1/books/{$free->id}/access")
            ->assertOk()
            ->assertJsonPath('authenticated', false)
            ->assertJsonPath('can_read', true)
            ->assertJsonPath('owned', false);

        $this->getJson("/api/v1/books/{$paid->id}/access")
            ->assertOk()
            ->assertJsonPath('authenticated', false)
            ->assertJsonPath('can_read', false);
    }

    public function test_admin_replace_file_stores_new_before_deleting_old(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $book = Book::factory()->create([
            'file_path' => 'books/km/old.pdf',
            'file_disk' => 'local',
            'status' => Book::STATUS_DRAFT,
        ]);

        Storage::disk('local')->put($book->file_path, $this->fakePdf(4));

        $this->actingAs($admin, 'sanctum')
            ->put("/api/v1/admin/books/{$book->id}", [
                'title' => $book->title,
                'file' => UploadedFile::fake()->createWithContent('book.pdf', $this->fakePdf(2)),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('book.file_disk', 'local');

        $fresh = $book->fresh();

        $this->assertNotSame('books/km/old.pdf', $fresh->file_path);
        $this->assertNotNull($fresh->file_hash);
        Storage::disk('local')->assertMissing('books/km/old.pdf');
        Storage::disk('local')->assertExists($fresh->file_path);
    }

    public function test_review_show_exposes_signed_preview_url_that_works_without_auth(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $book = Book::factory()->pending()->create([
            'file_path' => 'books/km/abc.pdf',
            'file_disk' => 'local',
        ]);

        Storage::disk('local')->put($book->file_path, $this->fakePdf(4));

        $url = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/book-reviews/{$book->id}")
            ->assertOk()
            ->json('book.preview_url');

        $this->assertStringContainsString('/v1/storage/local/', $url);

        $this->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_expired_signed_preview_url_is_rejected(): void
    {
        Storage::fake('local');

        $book = Book::factory()->pending()->create([
            'file_path' => 'books/km/abc.pdf',
            'file_disk' => 'local',
        ]);

        Storage::disk('local')->put($book->file_path, $this->fakePdf(4));

        $url = app(BookStorageService::class)
            ->temporaryDocumentUrl('books/km/abc.pdf', 'local', -10);

        $this->get($url)->assertStatus(401);
    }
}
