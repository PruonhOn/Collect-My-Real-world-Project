<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Purchase;
use App\Models\SharedAccess;
use App\Models\SharingKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A book flagged `is_vip_only` is readable only with an active VIP membership.
 * Neither the free flag, a paid purchase, nor a sharing key unlocks it, and
 * neither the reader nor the stream endpoint may serve it to anyone else.
 */
class VipBookAccessTest extends TestCase
{
    use RefreshDatabase;

    private function vipBook(array $attributes = []): Book
    {
        $path = 'books/km/vip.pdf';
        Storage::fake('local');
        Storage::disk('local')->put($path, '%PDF-1.4 vip book content');

        return Book::factory()->vipOnly()->create(array_merge([
            'is_free' => false,
            'price' => 9.00,
            'is_published' => true,
            'file_path' => $path,
            'file_type' => 'application/pdf',
        ], $attributes));
    }

    private function vipMember(): User
    {
        return User::factory()->create([
            'vip_until' => now()->addYear(),
            'vip_plan' => 'vip-yearly',
        ]);
    }

    private function regularMember(): User
    {
        return User::factory()->create();
    }

    private function grantPurchase(User $user, Book $book): void
    {
        Purchase::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => $book->price,
            'status' => Purchase::STATUS_PAID,
            'activated_at' => now(),
        ]);
    }

    private function grantSharedAccess(User $user, Book $book): void
    {
        $key = SharingKey::create([
            'owner_id' => User::factory()->create()->id,
            'book_id' => $book->id,
            'max_uses' => SharingKey::MAX_USES,
            'used_count' => 0,
            'status' => SharingKey::STATUS_ACTIVE,
            'expires_at' => now()->addDays(30),
        ]);

        SharedAccess::create([
            'sharing_key_id' => $key->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => SharedAccess::STATUS_ACTIVE,
        ]);
    }

    // --- Active VIP membership is the only way in ---

    public function test_active_vip_member_can_read_vip_only_book(): void
    {
        $book = $this->vipBook();

        $this->actingAs($this->vipMember(), 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertOk()
            ->assertJsonPath('book.id', $book->id);
    }

    public function test_active_vip_member_can_stream_vip_only_book(): void
    {
        $book = $this->vipBook();

        $this->actingAs($this->vipMember(), 'sanctum')
            ->get("/api/v1/reader/{$book->id}/stream")
            ->assertOk()
            ->assertStreamedContent('%PDF-1.4 vip book content');
    }

    // --- Everyone else is denied ---

    public function test_regular_member_cannot_read_vip_only_book(): void
    {
        $book = $this->vipBook();

        $this->actingAs($this->regularMember(), 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertStatus(403)
            ->assertJsonPath('vip_required', true);
    }

    public function test_regular_member_cannot_stream_vip_only_book(): void
    {
        $book = $this->vipBook();

        $this->actingAs($this->regularMember(), 'sanctum')
            ->get("/api/v1/reader/{$book->id}/stream")
            ->assertStatus(403);
    }

    public function test_guest_cannot_read_vip_only_book(): void
    {
        $book = $this->vipBook();

        $this->getJson("/api/v1/reader/{$book->id}")
            ->assertStatus(401);
    }

    public function test_expired_vip_member_cannot_read_vip_only_book(): void
    {
        $user = User::factory()->create([
            'vip_until' => now()->subDay(),
            'vip_plan' => 'vip-yearly',
        ]);
        $book = $this->vipBook();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertStatus(403);
    }

    public function test_purchase_does_not_unlock_vip_only_book(): void
    {
        $user = $this->regularMember();
        $book = $this->vipBook();
        $this->grantPurchase($user, $book);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertStatus(403);
    }

    public function test_shared_access_does_not_unlock_vip_only_book(): void
    {
        $user = $this->regularMember();
        $book = $this->vipBook();
        $this->grantSharedAccess($user, $book);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertStatus(403);
    }

    public function test_vip_only_wins_over_free_flag(): void
    {
        $book = $this->vipBook(['is_free' => true, 'price' => 0]);

        $this->actingAs($this->regularMember(), 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertStatus(403)
            ->assertJsonPath('vip_required', true);
    }

    public function test_guest_cannot_read_vip_only_book_flagged_free(): void
    {
        $book = $this->vipBook(['is_free' => true, 'price' => 0]);

        $this->getJson("/api/v1/reader/{$book->id}")
            ->assertStatus(401);
    }

    // --- Reader feature endpoints inherit the gate ---

    public function test_regular_member_cannot_save_progress_on_vip_only_book(): void
    {
        $user = $this->regularMember();
        $book = $this->vipBook();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/reader/{$book->id}/progress", [
                'last_page' => 3,
                'total_pages' => 10,
            ])
            ->assertStatus(403);
    }

    public function test_regular_member_cannot_bookmark_vip_only_book(): void
    {
        $user = $this->regularMember();
        $book = $this->vipBook();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/reader/{$book->id}/bookmarks", [
                'page' => 2,
                'note' => 'nope',
            ])
            ->assertStatus(403);
    }

    // --- No dead-end purchase or sharing paths ---

    public function test_vip_only_book_cannot_be_purchased(): void
    {
        $user = $this->regularMember();
        $book = $this->vipBook();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/purchase", ['method' => 'mock'])
            ->assertStatus(422)
            ->assertJsonPath('vip_required', true);

        $this->assertDatabaseMissing('purchases', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_vip_only_book_cannot_be_shared(): void
    {
        $user = $this->vipMember();
        $book = $this->vipBook();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/share")
            ->assertStatus(403);

        $this->assertDatabaseMissing('sharing_keys', ['book_id' => $book->id]);
    }

    public function test_existing_key_cannot_be_redeemed_after_book_becomes_vip_only(): void
    {
        $owner = User::factory()->create();
        $redeemer = $this->regularMember();
        $book = $this->vipBook();

        $key = SharingKey::create([
            'owner_id' => $owner->id,
            'book_id' => $book->id,
            'max_uses' => SharingKey::MAX_USES,
            'used_count' => 0,
            'status' => SharingKey::STATUS_ACTIVE,
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($redeemer, 'sanctum')
            ->postJson('/api/v1/redeem', ['code' => $key->code])
            ->assertStatus(403)
            ->assertJsonPath('vip_required', true);

        $this->assertDatabaseMissing('shared_access', [
            'user_id' => $redeemer->id,
            'book_id' => $book->id,
        ]);
    }

    // --- Access endpoint and library stay honest ---

    public function test_guest_access_endpoint_reports_vip_only_book_as_unreadable(): void
    {
        $book = $this->vipBook(['is_free' => true, 'price' => 0]);

        $this->getJson("/api/v1/books/{$book->id}/access")
            ->assertOk()
            ->assertJsonPath('is_vip_only', true)
            ->assertJsonPath('can_read', false);
    }

    public function test_library_hides_vip_only_book_from_regular_member(): void
    {
        $user = $this->regularMember();
        $vipBook = $this->vipBook();
        $regularBook = Book::factory()->create(['is_free' => false, 'is_published' => true]);
        $this->grantPurchase($user, $vipBook);
        $this->grantPurchase($user, $regularBook);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/library')
            ->assertOk();

        $ids = collect($response->json('owned'))->pluck('id');
        $this->assertTrue($ids->contains($regularBook->id));
        $this->assertFalse($ids->contains($vipBook->id));
    }

    public function test_library_lists_vip_only_book_for_active_vip_member(): void
    {
        $book = $this->vipBook();

        $this->actingAs($this->vipMember(), 'sanctum')
            ->getJson('/api/v1/library')
            ->assertOk()
            ->assertJsonPath('vip.0.id', $book->id);
    }
}
