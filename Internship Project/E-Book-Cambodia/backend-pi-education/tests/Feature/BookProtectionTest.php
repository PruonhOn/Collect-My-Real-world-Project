<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Purchase;
use App\Models\User;
use App\Services\SignedStreamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Security tests for paid-book protection, temporary signed access and the
 * content version used to invalidate browser caches.
 */
class BookProtectionTest extends TestCase
{
    use RefreshDatabase;

    private function paidBook(string $path = 'books/km/paid.pdf', string $content = '%PDF-1.4 paid book content'): Book
    {
        Storage::fake('local');
        Storage::disk('local')->put($path, $content);

        return Book::factory()->create([
            'is_free' => false,
            'price' => 5.00,
            'is_published' => true,
            'file_path' => $path,
            'file_type' => 'application/pdf',
        ]);
    }

    private function freeBook(string $path = 'books/km/free.pdf', string $content = '%PDF-1.4 free book content'): Book
    {
        Storage::fake('local');
        Storage::disk('local')->put($path, $content);

        return Book::factory()->free()->create([
            'is_published' => true,
            'file_path' => $path,
            'file_type' => 'application/pdf',
        ]);
    }

    private function createPurchase(User $user, Book $book): void
    {
        Purchase::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => $book->price,
            'status' => Purchase::STATUS_PAID,
            'activated_at' => now(),
        ]);
    }

    // --- Free books keep their current public behaviour ---

    public function test_free_book_read_works_without_authentication(): void
    {
        $book = $this->freeBook();

        $this->getJson("/api/v1/reader/{$book->id}")
            ->assertOk()
            ->assertJsonPath('book.id', $book->id);
    }

    public function test_free_book_stream_works_without_authentication(): void
    {
        $book = $this->freeBook();

        $this->get("/api/v1/reader/{$book->id}/stream")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertStreamedContent('%PDF-1.4 free book content');
    }

    // --- Unauthorized access is rejected on the backend ---

    public function test_paid_book_stream_requires_authentication(): void
    {
        $book = $this->paidBook();

        $this->get("/api/v1/reader/{$book->id}/stream")
            ->assertStatus(401);
    }

    public function test_paid_book_stream_is_forbidden_without_access(): void
    {
        $user = User::factory()->create();
        $book = $this->paidBook();

        $this->actingAs($user, 'sanctum')
            ->get("/api/v1/reader/{$book->id}/stream")
            ->assertStatus(403);
    }

    public function test_paid_book_read_is_forbidden_without_access(): void
    {
        $user = User::factory()->create();
        $book = $this->paidBook();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertStatus(403);
    }

    // --- Lawful access is granted ---

    public function test_paid_book_stream_is_served_after_purchase(): void
    {
        $user = User::factory()->create();
        $book = $this->paidBook();
        $this->createPurchase($user, $book);

        $response = $this->actingAs($user, 'sanctum')
            ->get("/api/v1/reader/{$book->id}/stream");

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertStreamedContent('%PDF-1.4 paid book content');

        $cacheControl = strtolower((string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
    }

    public function test_vip_user_can_stream_paid_book_without_purchase(): void
    {
        $user = User::factory()->create([
            'vip_until' => now()->addYear(),
            'vip_plan' => 'vip-yearly',
        ]);
        $book = $this->paidBook();

        $this->actingAs($user, 'sanctum')
            ->get("/api/v1/reader/{$book->id}/stream")
            ->assertOk()
            ->assertStreamedContent('%PDF-1.4 paid book content');
    }

    public function test_expired_vip_user_cannot_stream_paid_book(): void
    {
        $user = User::factory()->create([
            'vip_until' => now()->subDay(),
            'vip_plan' => 'vip-yearly',
        ]);
        $book = $this->paidBook();

        $this->actingAs($user, 'sanctum')
            ->get("/api/v1/reader/{$book->id}/stream")
            ->assertStatus(403);
    }

    // --- IDOR / cross-user isolation ---

    public function test_another_users_purchase_does_not_grant_access(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $book = $this->paidBook();
        $this->createPurchase($owner, $book);

        $this->actingAs($intruder, 'sanctum')
            ->get("/api/v1/reader/{$book->id}/stream")
            ->assertStatus(403);

        $this->actingAs($intruder, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertStatus(403);
    }

    public function test_manipulated_book_id_is_validated_against_real_record(): void
    {
        $user = User::factory()->create();
        $book = $this->paidBook();
        $this->createPurchase($user, $book);

        $this->actingAs($user, 'sanctum')
            ->get('/api/v1/reader/999999/stream')
            ->assertStatus(404);
    }

    // --- Signed temporary links ---

    public function test_signed_stream_url_allows_temporary_access(): void
    {
        $user = User::factory()->create();
        $book = $this->paidBook();
        $this->createPurchase($user, $book);

        $expires = now()->addSeconds(300)->getTimestamp();
        $url = app(SignedStreamService::class)->generate($book->id, $expires, $user->id);

        $this->get($url)
            ->assertOk()
            ->assertStreamedContent('%PDF-1.4 paid book content');
    }

    public function test_signed_stream_url_cannot_be_reused_on_another_book(): void
    {
        $user = User::factory()->create();
        $first = $this->paidBook('books/km/first.pdf', 'first');

        Storage::fake('local');
        Storage::disk('local')->put('books/km/second.pdf', 'second');
        $second = Book::factory()->create([
            'is_free' => false,
            'price' => 5.00,
            'is_published' => true,
            'file_path' => 'books/km/second.pdf',
            'file_type' => 'application/pdf',
        ]);

        $expires = now()->addSeconds(300)->getTimestamp();
        $service = app(SignedStreamService::class);
        $urlForFirst = $service->generate($first->id, $expires, $user->id);

        $secondUrl = preg_replace('#/reader/\d+/stream#', "/reader/{$second->id}/stream", $urlForFirst);

        $this->get($secondUrl)
            ->assertStatus(401);
    }

    public function test_expired_signed_stream_url_is_rejected(): void
    {
        $user = User::factory()->create();
        $book = $this->paidBook();
        $this->createPurchase($user, $book);

        $expires = now()->subSeconds(10)->getTimestamp();
        $url = app(SignedStreamService::class)->generate($book->id, $expires, $user->id);

        $this->get($url)
            ->assertStatus(401);
    }

    public function test_tampered_signature_is_rejected(): void
    {
        $user = User::factory()->create();
        $book = $this->paidBook();
        $this->createPurchase($user, $book);

        $expires = now()->addSeconds(300)->getTimestamp();
        $url = app(SignedStreamService::class)->generate($book->id, $expires, $user->id);
        $tampered = substr($url, 0, -2).'zz';

        $this->get($tampered)
            ->assertStatus(401);
    }

    // --- Range / chunked serving ---

    public function test_range_request_serves_partial_content(): void
    {
        $user = User::factory()->create();
        $book = $this->paidBook();
        $this->createPurchase($user, $book);

        $content = '%PDF-1.4 paid book content';
        $this->actingAs($user, 'sanctum')
            ->get("/api/v1/reader/{$book->id}/stream", ['Range' => 'bytes=5-9'])
            ->assertStatus(206)
            ->assertHeader('Accept-Ranges', 'bytes')
            ->assertHeader('Content-Range', 'bytes 5-9/'.strlen($content))
            ->assertStreamedContent('1.4 p');
    }

    // --- No permanent public URL for paid PDFs ---

    public function test_paid_pdf_has_no_permanent_public_url(): void
    {
        Storage::fake('public');
        $book = $this->paidBook();

        $this->assertFalse(Storage::disk('public')->exists($book->file_path));

        $this->get('/storage/'.$book->file_path)
            ->assertStatus(403);

        $this->get('/storage/'.$book->file_path)
            ->assertStatus(403);
    }

    // --- Reader metadata powers the local cache ---

    public function test_read_exposes_cache_version_and_signed_stream_url(): void
    {
        $book = $this->freeBook();

        $this->getJson("/api/v1/reader/{$book->id}")
            ->assertOk()
            ->assertJsonStructure(['access' => ['version', 'expires_at', 'stream_url', 'can_read']])
            ->assertJsonPath('access.version', $book->contentVersion())
            ->assertJsonPath('access.can_read', true);
    }

    public function test_content_version_changes_when_file_is_replaced(): void
    {
        Storage::fake('local');
        $path = 'books/km/replaced.pdf';
        Storage::disk('local')->put($path, 'A');

        $book = Book::factory()->free()->create([
            'is_published' => true,
            'file_path' => $path,
            'file_type' => 'application/pdf',
        ]);

        $versionBefore = $book->contentVersion();

        Storage::disk('local')->put($path, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
        $book->update(['file_size' => 36]);

        $this->assertNotSame($versionBefore, $book->fresh()->contentVersion());
    }
}
