<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;
    use SeedsRolesAndPermissions;

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/messages')
            ->assertUnauthorized();
    }

    public function test_index_returns_only_my_threads_with_the_conversation_map(): void
    {
        $this->seedRolesAndPermissions();
        $me = $this->adminUser(['name' => 'Me']);
        $other = $this->staffUser(['name' => 'Other']);
        $third = User::factory()->create(['name' => 'Third']);
        $sent = Message::factory()->create(['sender_id' => $me->id, 'recipient_id' => $other->id, 'body' => 'Me to other']);
        Message::factory()->create(['sender_id' => $other->id, 'recipient_id' => $me->id, 'body' => 'Other to me']);
        Message::factory()->create(['sender_id' => $third->id, 'recipient_id' => $other->id, 'body' => 'Unrelated']);
        Message::factory()->create(['sender_id' => $me->id, 'recipient_id' => $other->id, 'parent_id' => $sent->id, 'body' => 'Threaded reply']);

        $this->actingAs($me, 'sanctum')
            ->getJson('/api/v1/messages')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['meta' => ['pagination', 'conversation_map']])
            ->assertJsonCount(1, 'meta.conversation_map')
            ->assertJsonPath('meta.conversation_map.0.user.id', $other->id)
            ->assertJsonPath('meta.conversation_map.0.unread_count', 1);
    }

    public function test_store_creates_a_message_and_returns_201(): void
    {
        $this->seedRolesAndPermissions();
        $sender = $this->adminUser(['name' => 'Message Sender']);
        $recipient = $this->staffUser(['name' => 'Message Recipient']);

        $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/messages', [
                'recipient_id' => $recipient->id,
                'subject' => 'Welcome',
                'body' => 'Hello from the office.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.sender.id', $sender->id)
            ->assertJsonPath('data.recipient.id', $recipient->id)
            ->assertJsonPath('data.body', 'Hello from the office.');

        $this->assertDatabaseHas('messages', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'body' => 'Hello from the office.',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'message.sent']);
    }

    public function test_store_returns_422_when_the_recipient_does_not_exist(): void
    {
        $this->seedRolesAndPermissions();
        $sender = $this->adminUser();

        $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/messages', [
                'recipient_id' => 99999,
                'body' => 'Hello',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recipient_id']);
    }

    public function test_store_returns_422_when_body_is_missing(): void
    {
        $this->seedRolesAndPermissions();
        $sender = $this->adminUser();
        $recipient = $this->staffUser();

        $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/messages', ['recipient_id' => $recipient->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['body']);
    }

    public function test_store_returns_403_without_the_send_or_respond_permission(): void
    {
        $this->seedRolesAndPermissions();
        $user = User::factory()->create();
        $recipient = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', [
                'recipient_id' => $recipient->id,
                'body' => 'Nope',
            ])
            ->assertForbidden();
    }

    public function test_conversation_returns_422_when_opening_a_conversation_with_myself(): void
    {
        $this->seedRolesAndPermissions();
        $me = $this->adminUser();

        $this->actingAs($me, 'sanctum')
            ->getJson("/api/v1/messages/conversation/{$me->id}")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'You cannot open a conversation with yourself.');
    }

    public function test_conversation_returns_only_messages_between_me_and_the_other_user(): void
    {
        $this->seedRolesAndPermissions();
        $me = $this->adminUser(['name' => 'Me']);
        $other = $this->staffUser(['name' => 'Other']);
        $third = User::factory()->create(['name' => 'Third']);
        Message::factory()->create(['sender_id' => $me->id, 'recipient_id' => $other->id, 'body' => 'Me to other']);
        Message::factory()->create(['sender_id' => $other->id, 'recipient_id' => $me->id, 'body' => 'Other to me']);
        Message::factory()->create(['sender_id' => $me->id, 'recipient_id' => $third->id, 'body' => 'Me to third']);

        $this->actingAs($me, 'sanctum')
            ->getJson("/api/v1/messages/conversation/{$other->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['body' => 'Me to third']);
    }

    public function test_conversation_marks_inbound_messages_as_read_but_not_my_own(): void
    {
        $this->seedRolesAndPermissions();
        $me = $this->adminUser();
        $other = $this->staffUser();
        $inbound = Message::factory()->create(['sender_id' => $other->id, 'recipient_id' => $me->id, 'body' => 'Unread inbound']);
        $outbound = Message::factory()->create(['sender_id' => $me->id, 'recipient_id' => $other->id, 'body' => 'My sent message']);

        $this->actingAs($me, 'sanctum')
            ->getJson("/api/v1/messages/conversation/{$other->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertNotNull($inbound->refresh()->read_at);
        $this->assertNull($outbound->refresh()->read_at);
    }

    public function test_conversation_is_available_to_any_authenticated_user(): void
    {
        $this->seedRolesAndPermissions();
        $me = $this->adminUser(['name' => 'Me']);
        $other = $this->staffUser(['name' => 'Other']);
        Message::factory()->create(['sender_id' => $me->id, 'recipient_id' => $other->id, 'body' => 'Me to other']);

        $this->actingAs($other, 'sanctum')
            ->getJson("/api/v1/messages/conversation/{$me->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.body', 'Me to other');
    }
}
