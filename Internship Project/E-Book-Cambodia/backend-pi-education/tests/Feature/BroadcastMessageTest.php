<?php

namespace Tests\Feature;

use App\Models\BroadcastMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin broadcast messages ("To Web Page"): what an admin composes, and the
 * public feed every visitor sees when the page opens.
 */
class BroadcastMessageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Maintenance window',
            'body' => 'The library is offline on Sunday from 8am to 10am.',
            'type' => BroadcastMessage::TYPE_BANNER,
            'tone' => BroadcastMessage::TONE_WARNING,
            'is_active' => true,
        ], $overrides);
    }

    // --- Admin authoring ---

    public function test_admin_can_publish_a_banner_message(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/broadcast-messages', $this->payload())
            ->assertCreated()
            ->assertJsonPath('message.title', 'Maintenance window')
            ->assertJsonPath('message.type', BroadcastMessage::TYPE_BANNER)
            ->assertJsonPath('message.is_active', true);

        $this->assertDatabaseHas('broadcast_messages', [
            'title' => 'Maintenance window',
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_can_publish_a_popup_message_with_a_link(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/broadcast-messages', $this->payload([
                'type' => BroadcastMessage::TYPE_POPUP,
                'tone' => BroadcastMessage::TONE_INFO,
                'link_url' => 'https://example.test/events',
                'link_label' => 'See events',
            ]))
            ->assertCreated()
            ->assertJsonPath('message.type', BroadcastMessage::TYPE_POPUP)
            ->assertJsonPath('message.link_label', 'See events');
    }

    public function test_admin_can_deactivate_and_delete_a_message(): void
    {
        $admin = $this->admin();
        $message = BroadcastMessage::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/broadcast-messages/{$message->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('message.is_active', false);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/broadcast-messages/{$message->id}")
            ->assertOk();

        $this->assertDatabaseMissing('broadcast_messages', ['id' => $message->id]);
    }

    public function test_admin_can_schedule_a_message_to_start_later(): void
    {
        $startsAt = now()->addWeek();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/broadcast-messages', $this->payload([
                'starts_at' => $startsAt->toDateTimeString(),
            ]))
            ->assertCreated();

        $this->assertSame(
            $startsAt->toDateTimeString(),
            BroadcastMessage::sole()->starts_at->toDateTimeString(),
        );
    }

    public function test_a_message_requires_a_body(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/broadcast-messages', $this->payload(['body' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_an_unknown_type_or_tone_is_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/broadcast-messages', $this->payload(['type' => 'hologram']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/broadcast-messages', $this->payload(['tone' => 'neon']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('tone');
    }

    public function test_members_cannot_manage_broadcast_messages(): void
    {
        $message = BroadcastMessage::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/v1/admin/broadcast-messages', $this->payload())
            ->assertForbidden();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->deleteJson("/api/v1/admin/broadcast-messages/{$message->id}")
            ->assertForbidden();
    }

    public function test_guests_cannot_manage_broadcast_messages(): void
    {
        $this->postJson('/api/v1/admin/broadcast-messages', $this->payload())->assertUnauthorized();
    }

    public function test_admin_list_is_paginated_and_filterable(): void
    {
        $admin = $this->admin();
        BroadcastMessage::factory()->popup()->create(['title' => 'New arrivals live']);
        BroadcastMessage::factory()->create(['title' => 'Library closed']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/broadcast-messages?type=popup')
            ->assertOk()
            ->assertJsonCount(1, 'messages.data')
            ->assertJsonPath('messages.data.0.title', 'New arrivals live');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/broadcast-messages?search=closed')
            ->assertOk()
            ->assertJsonCount(1, 'messages.data');
    }

    // --- The public feed ---

    public function test_guests_see_active_messages_without_signing_in(): void
    {
        BroadcastMessage::factory()->create(['title' => 'Free shipping this week']);

        $this->getJson('/api/v1/broadcast-messages')
            ->assertOk()
            ->assertJsonPath('messages.0.title', 'Free shipping this week');
    }

    public function test_inactive_and_out_of_window_messages_are_hidden(): void
    {
        BroadcastMessage::factory()->inactive()->create(['title' => 'Draft notice']);
        BroadcastMessage::factory()->expired()->create(['title' => 'Last month promo']);
        BroadcastMessage::factory()->scheduledForTomorrow()->create(['title' => 'Tomorrow teaser']);

        $this->getJson('/api/v1/broadcast-messages')
            ->assertOk()
            ->assertJsonCount(0, 'messages');
    }

    public function test_the_feed_returns_banners_and_popups_newest_first(): void
    {
        BroadcastMessage::factory()->create(['title' => 'Older banner']);
        BroadcastMessage::factory()->popup()->create(['title' => 'Newer popup']);

        $this->getJson('/api/v1/broadcast-messages')
            ->assertOk()
            ->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.0.title', 'Newer popup')
            ->assertJsonPath('messages.0.type', BroadcastMessage::TYPE_POPUP)
            ->assertJsonPath('messages.1.title', 'Older banner');
    }

    public function test_the_public_feed_only_exposes_display_fields(): void
    {
        BroadcastMessage::factory()->create();

        $payload = $this->getJson('/api/v1/broadcast-messages')->assertOk()->json('messages.0');

        $this->assertEqualsCanonicalizing(
            ['id', 'title', 'body', 'type', 'tone', 'link_url', 'link_label'],
            array_keys($payload),
        );
    }

    public function test_a_message_inside_its_window_is_shown(): void
    {
        BroadcastMessage::factory()->create([
            'title' => 'Live now',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);

        $this->getJson('/api/v1/broadcast-messages')
            ->assertOk()
            ->assertJsonPath('messages.0.title', 'Live now');
    }
}
