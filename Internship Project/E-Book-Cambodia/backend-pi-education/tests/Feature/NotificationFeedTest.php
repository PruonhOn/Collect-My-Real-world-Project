<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\User;
use App\Notifications\BookStatusChanged;
use App\Notifications\NewChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The notification bell behind each portal: authors are told what a reviewer
 * did to their book, and members are told when an admin answers them. Covers the
 * endpoints each role reaches for, and the ownership boundary between them.
 */
class NotificationFeedTest extends TestCase
{
    use RefreshDatabase;

    private function submittedBook(Author $author): Book
    {
        return Book::factory()
            ->pending()
            ->create(['author_id' => $author->id, 'author' => $author->name]);
    }

    // --- Author: review verdicts ---

    public function test_author_is_notified_when_a_book_is_approved(): void
    {
        $author = Author::factory()->create();
        $admin = User::factory()->admin()->create();
        $book = $this->submittedBook($author);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$book->id}", ['decision' => 'approved'])
            ->assertOk();

        $notification = $author->unreadNotifications()->firstOrFail();

        $this->assertSame(BookStatusChanged::class, $notification->type);
        $this->assertSame('approved', $notification->data['action']);
        $this->assertSame($book->id, $notification->data['book_id']);
        $this->assertSame($book->title, $notification->data['book_title']);
    }

    public function test_rejection_notification_carries_the_review_note(): void
    {
        $author = Author::factory()->create();
        $admin = User::factory()->admin()->create();
        $book = $this->submittedBook($author);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$book->id}", [
                'decision' => 'rejected',
                'review_note' => 'The scan is too dark to read.',
            ])
            ->assertOk();

        $notification = $author->unreadNotifications()->firstOrFail();

        $this->assertSame('rejected', $notification->data['action']);
        $this->assertSame('The scan is too dark to read.', $notification->data['review_note']);
    }

    public function test_author_is_notified_when_a_book_is_published(): void
    {
        $author = Author::factory()->create();
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->approved()->create(['author_id' => $author->id, 'author' => $author->name]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$book->id}/publish")
            ->assertOk();

        $notification = $author->unreadNotifications()->firstOrFail();

        $this->assertSame('published', $notification->data['action']);
    }

    public function test_unpublishing_is_reported_as_unpublished_not_approved(): void
    {
        $author = Author::factory()->create();
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->published()->create(['author_id' => $author->id, 'author' => $author->name]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$book->id}/unpublish")
            ->assertOk();

        $notification = $author->unreadNotifications()->firstOrFail();

        // The status is approved again, but the author needs to know their live
        // book was taken down rather than that it was accepted.
        $this->assertSame('unpublished', $notification->data['action']);
        $this->assertSame(Book::STATUS_APPROVED, $notification->data['status']);
    }

    public function test_submitting_a_book_does_not_notify_the_author(): void
    {
        $author = Author::factory()->create();
        $book = Book::factory()->draft()->create(['author_id' => $author->id, 'author' => $author->name]);

        $book->transitionTo(Book::STATUS_PENDING);

        $this->assertSame(0, $author->notifications()->count());
    }

    public function test_editing_a_book_does_not_notify_the_author(): void
    {
        $author = Author::factory()->create();
        $book = $this->submittedBook($author);

        $book->update(['title' => 'A Better Title']);

        $this->assertSame(0, $author->notifications()->count());
    }

    public function test_a_book_without_an_author_is_not_an_error(): void
    {
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->pending()->create(['author_id' => null]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$book->id}", ['decision' => 'approved'])
            ->assertOk();

        $this->assertDatabaseCount('notifications', 0);
    }

    // --- Author: the feed itself ---

    public function test_author_can_read_and_clear_their_own_notifications(): void
    {
        $author = Author::factory()->create();
        $admin = User::factory()->admin()->create();
        $book = $this->submittedBook($author);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$book->id}", ['decision' => 'approved'])
            ->assertOk();

        $response = $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/author/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $notification = $response->json('notifications.0');

        $this->assertSame('book_status_changed', $notification['type']);
        $this->assertFalse($notification['read']);

        $this->actingAs($author, 'sanctum')
            ->patchJson("/api/v1/author/notifications/{$notification['id']}/read")
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/author/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 0)
            ->assertJsonPath('notifications.0.read', true);
    }

    public function test_author_can_mark_every_notification_read_at_once(): void
    {
        $author = Author::factory()->create();
        $admin = User::factory()->admin()->create();

        foreach ([$this->submittedBook($author), $this->submittedBook($author)] as $book) {
            $this->actingAs($admin, 'sanctum')
                ->postJson("/api/v1/admin/book-reviews/{$book->id}", ['decision' => 'approved'])
                ->assertOk();
        }

        $this->assertSame(2, $author->unreadNotifications()->count());

        $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/author/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertSame(0, $author->unreadNotifications()->count());
    }

    public function test_author_cannot_read_another_authors_notification(): void
    {
        $author = Author::factory()->create();
        $intruder = Author::factory()->create();
        $admin = User::factory()->admin()->create();
        $book = $this->submittedBook($author);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$book->id}", ['decision' => 'approved'])
            ->assertOk();

        $notification = $author->unreadNotifications()->firstOrFail();

        $this->actingAs($intruder, 'sanctum')
            ->patchJson("/api/v1/author/notifications/{$notification->id}/read")
            ->assertStatus(403);
    }

    public function test_author_cannot_read_a_members_notification(): void
    {
        $user = User::factory()->create();
        $author = Author::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'Is anyone there?'])
            ->assertStatus(201);

        $notification = $admin->unreadNotifications()->firstOrFail();

        $this->actingAs($author, 'sanctum')
            ->patchJson("/api/v1/author/notifications/{$notification->id}/read")
            ->assertStatus(403);
    }

    public function test_author_notifications_are_hidden_from_members(): void
    {
        $author = Author::factory()->create();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $book = $this->submittedBook($author);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$book->id}", ['decision' => 'approved'])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'notifications')
            ->assertJsonPath('unread_count', 0);
    }

    // --- Member: admin replies ---

    public function test_member_is_notified_when_an_admin_replies_in_the_conversation(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'How do I get a refund?'])
            ->assertStatus(201);

        $conversationId = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/conversations')
            ->json('conversations.0.id');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/messages', [
                'conversation_id' => $conversationId,
                'message' => 'Email us and we will sort it out.',
            ])
            ->assertStatus(201);

        $notification = $user->unreadNotifications()->firstOrFail();

        $this->assertSame(NewChatMessage::class, $notification->type);
        $this->assertSame($conversationId, $notification->data['conversation_id']);
        $this->assertSame('chat', $notification->data['source']);
    }

    public function test_member_is_notified_when_an_admin_sends_them_a_message(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/messages/send', [
                'user_id' => $user->id,
                'subject' => 'Notice',
                'message' => 'Your account has been verified.',
            ])
            ->assertStatus(201);

        $this->assertSame(1, $user->unreadNotifications()->count());
    }

    public function test_member_is_not_notified_of_their_own_message(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'Hello?'])
            ->assertStatus(201);

        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertSame(1, $admin->unreadNotifications()->count());
    }

    public function test_guest_contact_leaves_no_member_to_notify(): void
    {
        User::factory()->admin()->create();

        $this->postJson('/api/v1/contact', [
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'subject' => 'subjectGeneral',
            'message' => 'Hello.',
        ])->assertStatus(201);

        $this->assertSame(0, User::query()->where('role', '!=', 'admin')->get()->sum(
            fn (User $user) => $user->unreadNotifications()->count(),
        ));
    }

    public function test_member_can_read_and_clear_their_own_notifications(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'Question about my order.'])
            ->assertStatus(201);

        $conversationId = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/conversations')
            ->json('conversations.0.id');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/messages', [
                'conversation_id' => $conversationId,
                'message' => 'Here is your answer.',
            ])
            ->assertStatus(201);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $notification = $response->json('notifications.0');

        $this->assertSame('new_message', $notification['type']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }

    public function test_opening_the_conversation_clears_the_member_notification(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'Still waiting.'])
            ->assertStatus(201);

        $conversationId = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/conversations')
            ->json('conversations.0.id');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/messages', [
                'conversation_id' => $conversationId,
                'message' => 'Sorry for the wait.',
            ])
            ->assertStatus(201);

        $this->assertSame(1, $user->unreadNotifications()->count());

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/conversations/{$conversationId}/messages")
            ->assertOk();

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_members_cannot_read_an_admin_notification(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $intruder = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'Private.'])
            ->assertStatus(201);

        $notification = $admin->unreadNotifications()->firstOrFail();

        $this->actingAs($intruder, 'sanctum')
            ->patchJson("/api/v1/notifications/{$notification->id}/read")
            ->assertStatus(403);
    }

    public function test_notification_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
        $this->getJson('/api/v1/author/notifications')->assertUnauthorized();
        $this->postJson('/api/v1/notifications/read-all')->assertUnauthorized();
        $this->postJson('/api/v1/author/notifications/read-all')->assertUnauthorized();
    }
}
