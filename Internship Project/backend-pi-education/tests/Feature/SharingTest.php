<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Purchase;
use App\Models\SharedAccess;
use App\Models\SharingKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_owner_can_generate_sharing_key(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['is_published' => true, 'price' => 5.00]);

        Purchase::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => 5.00,
            'status' => 'paid',
            'activated_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/share")
            ->assertStatus(201)
            ->assertJsonPath('key.max_uses', 3)
            ->assertJsonPath('key.status', 'active');
    }

    public function test_non_owner_cannot_generate_key(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['is_published' => true, 'price' => 5.00]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/share")
            ->assertStatus(403)
            ->assertJsonPath('message', 'You must own this book before you can share it.');
    }

    public function test_existing_active_key_is_returned_not_duplicated(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['is_published' => true, 'price' => 5.00]);

        Purchase::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => 5.00,
            'status' => 'paid',
            'activated_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/share")
            ->assertStatus(201);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/share")
            ->assertOk()
            ->assertJsonPath('message', 'An active sharing key already exists for this book.');

        $this->assertDatabaseCount('sharing_keys', 1);
    }

    public function test_user_can_redeem_valid_key(): void
    {
        $owner = User::factory()->create();
        $reader = User::factory()->create();
        $book = Book::factory()->create(['is_published' => true, 'price' => 5.00]);

        $key = SharingKey::create([
            'owner_id' => $owner->id,
            'book_id' => $book->id,
            'code' => 'TESTCODE1234',
            'max_uses' => 3,
        ]);

        $this->actingAs($reader, 'sanctum')
            ->postJson('/api/v1/redeem', ['code' => 'TESTCODE1234'])
            ->assertStatus(201)
            ->assertJsonPath('message', 'Access granted! The book has been added to your library.');

        $key->refresh();
        $this->assertEquals(1, $key->used_count);
    }

    public function test_user_cannot_redeem_own_key(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['is_published' => true, 'price' => 5.00]);

        $key = SharingKey::create([
            'owner_id' => $user->id,
            'book_id' => $book->id,
            'code' => 'OWNKEY12345',
            'max_uses' => 3,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/redeem', ['code' => 'OWNKEY12345'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot redeem your own sharing key.');
    }

    public function test_full_key_cannot_be_redeemed(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['is_published' => true, 'price' => 5.00]);
        $key = SharingKey::create([
            'owner_id' => $owner->id,
            'book_id' => $book->id,
            'code' => 'FULLKEY000',
            'max_uses' => 2,
            'used_count' => 2,
        ]);

        $reader = User::factory()->create();
        $this->actingAs($reader, 'sanctum')
            ->postJson('/api/v1/redeem', ['code' => 'FULLKEY000'])
            ->assertStatus(422);
    }

    public function test_owner_can_revoke_shared_access(): void
    {
        $owner = User::factory()->create();
        $reader = User::factory()->create();
        $book = Book::factory()->create(['is_published' => true, 'price' => 5.00]);

        $key = SharingKey::create([
            'owner_id' => $owner->id,
            'book_id' => $book->id,
            'code' => 'REVOKE001',
            'max_uses' => 3,
            'used_count' => 1,
        ]);

        $access = SharedAccess::create([
            'sharing_key_id' => $key->id,
            'user_id' => $reader->id,
            'book_id' => $book->id,
            'status' => 'active',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/sharing/{$access->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Access revoked.');

        $access->refresh();
        $this->assertEquals('revoked', $access->status);
        $this->assertNotNull($access->revoked_at);
    }

    public function test_revoked_user_cannot_read_book(): void
    {
        $owner = User::factory()->create();
        $reader = User::factory()->create();
        $book = Book::factory()->create(['is_published' => true, 'price' => 5.00]);

        $key = SharingKey::create([
            'owner_id' => $owner->id,
            'book_id' => $book->id,
            'code' => 'REVREAD00',
            'max_uses' => 3,
        ]);

        $access = SharedAccess::create([
            'sharing_key_id' => $key->id,
            'user_id' => $reader->id,
            'book_id' => $book->id,
            'status' => 'active',
        ]);

        $this->actingAs($reader, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertOk();

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/sharing/{$access->id}")
            ->assertOk();

        $this->actingAs($reader, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertStatus(403);
    }

    public function test_enforces_max_3_uses(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['is_published' => true, 'price' => 5.00]);

        $key = SharingKey::create([
            'owner_id' => $owner->id,
            'book_id' => $book->id,
            'code' => 'LIMITTEST12',
            'max_uses' => 3,
            'used_count' => 3,
        ]);

        $reader = User::factory()->create();
        $this->actingAs($reader, 'sanctum')
            ->postJson('/api/v1/redeem', ['code' => 'LIMITTEST12'])
            ->assertStatus(422);
    }

    public function test_user_can_view_their_sharing(): void
    {
        $owner = User::factory()->create();
        $reader = User::factory()->create();
        $book = Book::factory()->create(['is_published' => true, 'price' => 5.00]);

        $key = SharingKey::create([
            'owner_id' => $owner->id,
            'book_id' => $book->id,
            'code' => 'VIEWSHARE00',
            'max_uses' => 3,
        ]);

        $this->actingAs($reader, 'sanctum')
            ->getJson('/api/v1/sharing')
            ->assertOk()
            ->assertJsonCount(0, 'generated')
            ->assertJsonCount(0, 'received');
    }

    public function test_invalid_code_returns_422(): void
    {
        $reader = User::factory()->create();
        $this->actingAs($reader, 'sanctum')
            ->postJson('/api/v1/redeem', ['code' => 'INVALIDCODE'])
            ->assertStatus(422);
    }
}
