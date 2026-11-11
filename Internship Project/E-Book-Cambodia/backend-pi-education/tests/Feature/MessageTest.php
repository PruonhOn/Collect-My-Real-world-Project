<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use App\Notifications\NewChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_send_a_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', [
                'subject' => 'Payment issue',
                'message' => 'I cannot complete my purchase.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('user_message.subject', 'Payment issue');

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'subject' => 'Payment issue',
        ]);
    }

    public function test_message_requires_a_body(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['subject' => 'Hello'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_user_can_send_message_with_selected_topic(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', [
                'topic' => 'subjectPayment',
                'subject' => 'Card declined',
                'message' => 'My payment failed.',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'topic' => 'Payment Support',
            'subject' => 'Card declined',
        ]);
    }

    public function test_user_can_list_only_their_own_messages(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Message::factory()->create(['user_id' => $user->id]);
        Message::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/my/messages')
            ->assertOk()
            ->assertJsonCount(1, 'messages.data');

        $messages = $this->actingAs($other, 'sanctum')
            ->getJson('/api/v1/my/messages')
            ->json('messages.data');

        $this->assertCount(1, $messages);
    }

    public function test_guest_cannot_send_a_message(): void
    {
        $this->postJson('/api/v1/messages', [
            'subject' => 'Hi',
            'message' => 'Hello team.',
        ])->assertStatus(401);
    }

    public function test_admin_can_list_all_messages_and_reply(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $message = Message::factory()->create(['user_id' => $user->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/messages')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/messages/{$message->id}/reply", ['reply' => 'We have fixed it.'])
            ->assertOk()
            ->assertJsonPath('user_message.reply', 'We have fixed it.');

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'reply' => 'We have fixed it.',
            'replied_at' => $message->fresh()->replied_at,
        ]);
    }

    public function test_admin_can_delete_a_message(): void
    {
        $admin = User::factory()->admin()->create();
        $message = Message::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/messages/{$message->id}")
            ->assertOk();

        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }

    public function test_admin_can_send_a_message_to_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/messages/send', [
                'user_id' => $user->id,
                'subject' => 'Welcome',
                'message' => 'Thanks for joining!',
            ])
            ->assertStatus(201)
            ->assertJsonPath('user_message.from_admin', true);

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'subject' => 'Welcome',
            'from_admin' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/my/messages')
            ->assertOk()
            ->assertJsonCount(1, 'messages.data')
            ->assertJsonPath('unread_count', 1);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/messages/unread-count')
            ->assertJsonPath('unread_count', 0);
    }

    public function test_non_admin_cannot_send_a_message_to_a_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/messages/send', [
                'user_id' => $other->id,
                'subject' => 'Hi',
                'message' => 'Hello.',
            ])
            ->assertStatus(403);
    }

    public function test_non_admin_cannot_access_admin_messages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/messages')
            ->assertStatus(403);
    }

    public function test_admin_sees_unread_count_for_new_user_messages(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Message::factory()->create(['user_id' => $user->id]);
        Message::factory()->create(['user_id' => $user->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/messages/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 2);
    }

    public function test_admin_mark_read_clears_admin_unread_count(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $message = Message::factory()->create(['user_id' => $user->id]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/messages/read')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($message->fresh()->admin_read_at);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/messages/unread-count')
            ->assertJsonPath('unread_count', 0);
    }

    public function test_admin_created_collaborators_are_not_unread(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/messages', ['name' => 'Sunrise School'])
            ->assertStatus(201);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/messages/unread-count')
            ->assertJsonPath('unread_count', 0);
    }

    public function test_guest_contact_counts_as_admin_unread(): void
    {
        $admin = User::factory()->admin()->create();
        $this->postJson('/api/v1/contact', [
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'subject' => 'subjectGeneral',
            'message' => 'Hello.',
        ])->assertStatus(201);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/messages/unread-count')
            ->assertJsonPath('unread_count', 1);
    }

    public function test_admin_can_add_a_collaborator(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/messages', [
                'name' => 'Sunrise School',
                'email' => 'hello@sunrise.edu.kh',
                'phone' => '+85512345678',
                'program' => 'School',
                'message' => 'We want to partner.',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('messages', [
            'sender_name' => 'Sunrise School',
            'sender_email' => 'hello@sunrise.edu.kh',
            'subject' => 'Partnership / Business',
            'message' => '[School] We want to partner.',
            'user_id' => null,
        ]);
    }

    public function test_non_admin_cannot_add_a_collaborator(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/messages', ['name' => 'Test'])
            ->assertStatus(403);
    }

    public function test_admin_can_edit_a_collaborator(): void
    {
        $admin = User::factory()->admin()->create();
        $message = Message::factory()->create([
            'sender_name' => 'Old School',
            'subject' => 'Partnership / Business',
            'message' => '[School] Old notes',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/messages/{$message->id}", [
                'name' => 'New River Academy',
                'email' => 'info@newriver.edu.kh',
                'phone' => '+85598765432',
                'program' => 'NGO',
                'message' => 'Updated partnership notes.',
            ])
            ->assertOk()
            ->assertJsonPath('user_message.sender_name', 'New River Academy');

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'sender_name' => 'New River Academy',
            'sender_email' => 'info@newriver.edu.kh',
            'message' => '[NGO] Updated partnership notes.',
        ]);
    }

    public function test_admin_update_requires_name(): void
    {
        $admin = User::factory()->admin()->create();
        $message = Message::factory()->create(['subject' => 'Partnership / Business']);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/messages/{$message->id}", ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_admin_can_upload_and_remove_collaborator_photo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $created = $this->actingAs($admin, 'sanctum')
            ->post('/api/v1/admin/messages', [
                'name' => 'Sunrise School',
                'message' => 'Hello',
                'photo' => UploadedFile::fake()->image('logo.jpg'),
            ])
            ->assertStatus(201)
            ->json('user_message');

        $this->assertNotNull($created['photo']);
        Storage::disk('public')->assertExists($created['photo']);

        $this->actingAs($admin, 'sanctum')
            ->post('/api/v1/admin/messages', [
                'name' => 'Sunrise School',
                'message' => 'Hello',
            ])
            ->assertStatus(201)
            ->assertJsonPath('user_message.photo', null);

        $this->actingAs($admin, 'sanctum')
            ->put("/api/v1/admin/messages/{$created['id']}", [
                'name' => 'Sunrise School',
                'remove_photo' => true,
            ])
            ->assertOk()
            ->assertJsonPath('user_message.photo', null);

        Storage::disk('public')->assertMissing($created['photo']);
    }

    public function test_admin_can_filter_collaboration_requests(): void
    {
        $admin = User::factory()->admin()->create();
        Message::factory()->create(['subject' => 'Partnership / Business']);
        Message::factory()->create(['subject' => 'General Inquiry']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/messages?collaborators=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject', 'Partnership / Business');
    }

    public function test_user_sees_admin_reply_in_their_list(): void
    {
        $user = User::factory()->create();
        Message::factory()->replied()->create([
            'user_id' => $user->id,
            'subject' => 'VIP upgrade',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/my/messages')
            ->assertOk()
            ->assertJsonPath('messages.data.0.is_replied', true)
            ->assertJsonPath('messages.data.0.reply', fn ($reply) => is_string($reply) && $reply !== '');
    }

    public function test_unread_count_is_zero_when_no_replies(): void
    {
        $user = User::factory()->create();
        Message::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/my/messages/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }

    public function test_unread_count_counts_replied_unread_messages(): void
    {
        $user = User::factory()->create();
        Message::factory()->replied()->create(['user_id' => $user->id]);
        Message::factory()->replied()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/my/messages')
            ->assertOk()
            ->assertJsonPath('unread_count', 2);
    }

    public function test_mark_read_clears_unread_count(): void
    {
        $user = User::factory()->create();
        Message::factory()->replied()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/my/messages/read')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($user->messages()->first()->fresh()->read_at);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/my/messages/unread-count')
            ->assertJsonPath('unread_count', 0);
    }

    public function test_guest_contact_messages_are_not_part_of_users_unread_count(): void
    {
        $user = User::factory()->create();
        $this->postJson('/api/v1/contact', [
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'subject' => 'subjectGeneral',
            'message' => 'Hello.',
        ])->assertStatus(201);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/my/messages/unread-count')
            ->assertJsonPath('unread_count', 0);
    }

    public function test_guest_can_submit_public_contact_form(): void
    {
        $this->postJson('/api/v1/contact', [
            'name' => 'Sokha Chan',
            'email' => 'sokha@example.com',
            'phone' => '+85512345678',
            'subject' => 'subjectPayment',
            'message' => 'My payment did not go through.',
        ])->assertStatus(201);

        $this->assertDatabaseHas('messages', [
            'user_id' => null,
            'sender_name' => 'Sokha Chan',
            'sender_email' => 'sokha@example.com',
            'subject' => 'Payment Support',
        ]);
    }

    public function test_logged_in_user_contact_is_linked_to_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/contact', [
                'name' => $user->name,
                'email' => $user->email,
                'subject' => 'subjectGeneral',
                'message' => 'Hello there.',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'subject' => 'General Inquiry',
        ]);
    }

    public function test_contact_form_requires_name_email_subject_and_message(): void
    {
        $this->postJson('/api/v1/contact', [
            'name' => '',
            'email' => 'not-an-email',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'subject', 'message']);
    }

    public function test_admin_dashboard_includes_message_count(): void
    {
        $admin = User::factory()->admin()->create();
        Message::factory()->create();
        Message::factory()->replied()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('stats.pending_messages', 1);
    }

    public function test_user_and_admin_exchange_messages_in_a_conversation(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $sent = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', [
                'topic' => 'subjectAccount',
                'subject' => 'Access problem',
                'message' => 'I cannot open my book.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('user_message.sender_id', $user->id)
            ->assertJsonPath('user_message.receiver_id', $admin->id);

        $conversationId = $sent->json('conversation.id');

        $adminThread = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/conversations/{$conversationId}/messages")
            ->assertOk();

        $this->assertCount(1, $adminThread->json('messages'));
        $this->assertSame('I cannot open my book.', $adminThread->json('messages.0.message'));

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/messages', [
                'conversation_id' => $conversationId,
                'message' => 'Please try again now.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('user_message.sender_id', $admin->id)
            ->assertJsonPath('user_message.receiver_id', $user->id);

        $userThread = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/conversations/{$conversationId}/messages")
            ->assertOk();

        $this->assertCount(2, $userThread->json('messages'));
        $this->assertSame('I cannot open my book.', $userThread->json('messages.0.message'));
        $this->assertSame('Please try again now.', $userThread->json('messages.1.message'));
        $this->assertTrue($userThread->json('messages.0.is_mine'));
        $this->assertFalse($userThread->json('messages.1.is_mine'));

        $userUnread = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/my/messages/unread-count')
            ->assertJsonPath('unread_count', 0);
    }

    public function test_opening_a_conversation_marks_inbound_messages_read(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'Help needed.'])
            ->assertStatus(201);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/messages/unread-count')
            ->assertJsonPath('unread_count', 1);

        $conversationId = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/conversations')
            ->json('conversations.0.id');

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/conversations/{$conversationId}/messages")
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/messages/unread-count')
            ->assertJsonPath('unread_count', 0);
    }

    public function test_user_cannot_read_an_unrelated_conversation(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'Private data.'])
            ->assertStatus(201);

        $conversationId = $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/conversations')
            ->json('conversations.0.id');

        $this->actingAs($intruder, 'sanctum')
            ->getJson("/api/v1/conversations/{$conversationId}/messages")
            ->assertStatus(403);

        $this->actingAs($intruder, 'sanctum')
            ->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonPath('conversations', []);
    }

    public function test_user_cannot_send_into_an_unrelated_conversation(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'Hi.'])
            ->assertStatus(201);

        $conversationId = $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/conversations')
            ->json('conversations.0.id');

        $this->actingAs($intruder, 'sanctum')
            ->postJson('/api/v1/messages', [
                'conversation_id' => $conversationId,
                'message' => 'Sneaky.',
            ])
            ->assertStatus(403);
    }

    public function test_user_cannot_mark_someone_elses_message_read(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $message = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/messages/send', [
                'user_id' => $owner->id,
                'subject' => 'Notice',
                'message' => 'Important notice.',
            ])
            ->assertStatus(201)
            ->json('user_message');

        $this->actingAs($intruder, 'sanctum')
            ->patchJson("/api/v1/messages/{$message['id']}/read")
            ->assertStatus(403);

        $this->actingAs($owner, 'sanctum')
            ->patchJson("/api/v1/messages/{$message['id']}/read")
            ->assertOk()
            ->assertJsonPath('user_message.read_at', fn ($value) => $value !== null);
    }

    public function test_repeated_send_uses_the_same_conversation(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'First.'])
            ->assertStatus(201);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'Second.'])
            ->assertStatus(201);

        $conversations = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/conversations')
            ->assertOk()
            ->json('conversations');

        $this->assertCount(1, $conversations);
        $this->assertCount(2, $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/conversations/{$conversations[0]['id']}/messages")
            ->json('messages'));
    }

    public function test_user_message_creates_one_admin_notification(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'Hello Admin.'])
            ->assertStatus(201);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'notifiable_type' => (new User)->getMorphClass(),
            'type' => NewChatMessage::class,
        ]);

        $count = $admin->notifications()->where('type', NewChatMessage::class)->count();
        $this->assertSame(1, $count, 'A repeated page refresh must not duplicate the notification.');
    }

    public function test_admin_can_list_and_mark_notifications_read(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['topic' => 'subjectGeneral', 'message' => 'I need help.'])
            ->assertStatus(201);

        $notifications = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $notification = $notifications->json('notifications.0');
        $this->assertSame('new_message', $notification['type']);
        $this->assertArrayHasKey('conversation_id', $notification['data']);
        $this->assertSame($user->name, $notification['data']['sender_name']);
        $this->assertFalse($notification['read']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/notifications/{$notification['id']}/read")
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 0)
            ->assertJsonPath('notifications.0.read', true);
    }

    public function test_opening_a_conversation_marks_message_notifications_read(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'When will you reply?'])
            ->assertStatus(201);

        $conversationId = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/conversations')
            ->json('conversations.0.id');

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/conversations/{$conversationId}/messages")
            ->assertOk();

        $this->assertSame(0, $admin->unreadNotifications()->where('type', NewChatMessage::class)->count());
    }

    public function test_admin_sending_a_message_does_not_notify_itself(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/messages/send', [
                'user_id' => $user->id,
                'subject' => 'Notice',
                'message' => 'Hello there.',
            ])
            ->assertStatus(201);

        $this->assertSame(0, $admin->notifications()->where('type', NewChatMessage::class)->count());
    }

    public function test_user_cannot_read_an_admin_notification(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'Private.'])
            ->assertStatus(201);

        $notification = $admin->unreadNotifications()->first();

        $this->actingAs($intruder, 'sanctum')
            ->patchJson("/api/v1/admin/notifications/{$notification->id}/read")
            ->assertStatus(403);
    }

    public function test_admin_sees_all_user_conversations_separately(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['Alice', 'Bob', 'Carol'] as $name) {
            $user = User::factory()->create(['name' => $name]);
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/v1/messages', ['message' => "Message from {$name}."])
                ->assertStatus(201);
        }

        $conversations = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/conversations')
            ->assertOk()
            ->json('conversations');

        $this->assertCount(3, $conversations);

        $names = collect($conversations)->pluck('other_participant.name')->sort()->values();
        $this->assertSame(['Alice', 'Bob', 'Carol'], $names->all());

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/messages/unread-count')
            ->assertJsonPath('unread_count', 3);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 3);
    }

    // --- Every inbound message source announces itself ---

    public function test_guest_contact_form_notifies_the_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->postJson('/api/v1/contact', [
            'name' => 'Walk In Guest',
            'email' => 'guest@example.com',
            'subject' => 'subjectGeneral',
            'message' => 'Is the new textbook available?',
        ])->assertStatus(201);

        $notification = $admin->notifications()->first();

        $this->assertNotNull($notification, 'A guest contact submission was not announced.');
        $this->assertSame(NewChatMessage::class, $notification->type);
        $this->assertSame('contact', $notification->data['source']);
        $this->assertSame('Walk In Guest', $notification->data['sender_name']);
        $this->assertSame('guest@example.com', $notification->data['sender_email']);
        $this->assertNull($notification->data['conversation_id']);
        $this->assertSame('General Inquiry', $notification->data['subject']);
    }

    public function test_partnership_enquiry_notifies_the_admin_as_a_collaboration(): void
    {
        $admin = User::factory()->admin()->create();

        $this->postJson('/api/v1/contact', [
            'name' => 'School Principal',
            'email' => 'principal@example.com',
            'subject' => 'subjectPartnership',
            'message' => 'We would like to distribute your books.',
        ])->assertStatus(201);

        $notification = $admin->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('collaboration', $notification->data['source']);
        $this->assertSame('Partnership / Business', $notification->data['subject']);
    }

    public function test_a_member_chat_is_announced_as_a_chat_source(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Member One']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'Hello there.'])
            ->assertStatus(201);

        $notification = $admin->notifications()->first();

        $this->assertSame('chat', $notification->data['source']);
        $this->assertNotNull($notification->data['conversation_id']);
        $this->assertSame('Member One', $notification->data['sender_name']);
    }

    public function test_every_inbound_source_lands_in_the_admin_notifications_api(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'Chat message.'])
            ->assertStatus(201);

        $this->postJson('/api/v1/contact', [
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'subject' => 'subjectBook',
            'message' => 'Support needed.',
        ])->assertStatus(201);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 2);

        $notifications = $response->json('notifications');

        $this->assertSame(['new_message', 'new_message'], array_column($notifications, 'type'));

        // Both rows can share a created_at second, so compare the set of
        // sources rather than relying on order.
        $sources = array_column(array_column($notifications, 'data'), 'source');
        sort($sources);

        $this->assertSame(['chat', 'contact'], $sources);
    }

    public function test_admin_reply_does_not_announce_anything(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages', ['message' => 'Please help.'])
            ->assertStatus(201);

        $messageId = $response->json('user_message.id');

        $this->assertSame(1, $admin->unreadNotifications()->count());

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/messages/{$messageId}/reply", ['reply' => 'On it.'])
            ->assertOk();

        $this->assertSame(1, $admin->notifications()->count(), 'The admin must not announce their own reply.');
    }

    public function test_adding_a_collaborator_record_does_not_announce_anything(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/messages', [
                'name' => 'Publisher',
                'email' => 'pub@example.com',
                'message' => 'A partner.',
            ])
            ->assertStatus(201);

        $this->assertSame(0, $admin->notifications()->count());
    }
}
