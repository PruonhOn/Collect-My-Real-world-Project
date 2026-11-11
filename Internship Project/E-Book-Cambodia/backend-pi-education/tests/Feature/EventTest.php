<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    // --- Public feed ---

    public function test_public_feed_shows_only_published_events_featured_first(): void
    {
        $featured = Event::factory()->featured()->published()->create(['title' => 'Featured Event']);
        $regular = Event::factory()->published()->create(['title' => 'Regular Event']);
        Event::factory()->draft()->create(['title' => 'Draft Event']);
        Event::factory()->cancelled()->create(['title' => 'Cancelled Event']);

        $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonPath('events.0.title', 'Featured Event')
            ->assertJsonCount(2, 'events')
            ->assertJsonMissingPath('events.*.title', 'Draft Event')
            ->assertJsonMissingPath('events.*.title', 'Cancelled Event');

        $this->assertDatabaseHas('events', ['id' => $featured->id, 'is_featured' => true]);
        $this->assertTrue($featured->is_featured);
        $regular = Event::query()->find($regular->id);
        $this->assertFalse($regular->is_featured);
    }

    public function test_public_feed_returns_expected_fields_and_spots(): void
    {
        Event::factory()->published()->create([
            'title' => 'Reading Challenge',
            'category' => 'Workshop',
            'location' => 'Phnom Penh',
            'max_participants' => 5,
        ]);

        $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonStructure([
                'events' => [
                    '*' => [
                        'id', 'title', 'description', 'cover_image', 'category',
                        'start_date', 'end_date', 'location', 'organizer',
                        'registration_link', 'contact_phone', 'contact_email',
                        'max_participants', 'fee_type', 'fee_amount', 'speaker',
                        'social_links', 'status', 'is_featured', 'spots_left',
                        'registrations_count',
                    ],
                ],
            ])
            ->assertJsonPath('events.0.spots_left', 5);
    }

    public function test_public_show_returns_404_for_draft_event(): void
    {
        $draft = Event::factory()->draft()->create();

        $this->getJson("/api/v1/events/{$draft->id}")->assertNotFound();
    }

    public function test_public_show_returns_registration_state_for_logged_in_user(): void
    {
        $event = Event::factory()->published()->create();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('event.is_registered', false);

        EventRegistration::create(['event_id' => $event->id, 'user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('event.is_registered', true);
    }

    public function test_public_show_returns_registration_state_with_bearer_token(): void
    {
        $event = Event::factory()->published()->create();
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/v1/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('event.is_registered', false);

        EventRegistration::create(['event_id' => $event->id, 'user_id' => $user->id]);

        $this->withToken($token)
            ->getJson("/api/v1/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('event.is_registered', true);
    }

    // --- Access control ---

    public function test_guest_cannot_access_admin_events(): void
    {
        $this->getJson('/api/v1/admin/events')->assertUnauthorized();
    }

    public function test_member_cannot_access_admin_events(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/events')
            ->assertForbidden();
    }

    // --- Admin management ---

    public function test_admin_can_list_events_paginated_with_filters(): void
    {
        $admin = User::factory()->admin()->create();
        Event::factory()->count(25)->create();
        Event::factory()->draft()->create(['title' => 'Solo Draft', 'category' => 'Community']);
        Event::factory()->featured()->published()->create(['title' => 'Hero Featured']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/events?per_page=10')
            ->assertOk()
            ->assertJsonCount(10, 'events.data');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/events?search=Solo')
            ->assertOk()
            ->assertJsonCount(1, 'events.data')
            ->assertJsonPath('events.data.0.title', 'Solo Draft');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/events?status=draft')
            ->assertOk()
            ->assertJsonCount(1, 'events.data');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/events?featured=1')
            ->assertOk()
            ->assertJsonPath('events.data.0.title', 'Hero Featured');
    }

    public function test_admin_can_create_event_with_all_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/events', [
                'title' => 'New Reading Challenge',
                'description' => 'Join us this month.',
                'category' => 'Workshop',
                'start_date' => '2026-11-10T14:30:00',
                'end_date' => '2026-11-10T17:00:00',
                'location' => 'Phnom Penh',
                'organizer' => 'Pi Education',
                'registration_link' => 'https://example.org/register',
                'contact_phone' => '012345678',
                'contact_email' => 'events@example.org',
                'max_participants' => 120,
                'fee_type' => 'paid',
                'fee_amount' => 5.5,
                'speaker' => 'Dr. Ratanak',
                'social_links' => ['https://facebook.com/pi', 'https://t.me/pi'],
                'status' => 'published',
                'is_featured' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('event.title', 'New Reading Challenge')
            ->assertJsonPath('event.status', 'published')
            ->assertJsonPath('event.is_featured', true);

        $this->assertDatabaseHas('events', [
            'id' => $response->json('event.id'),
            'fee_type' => 'paid',
            'status' => 'published',
        ]);
    }

    public function test_admin_event_creation_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/events', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'description', 'start_date', 'location', 'status', 'fee_type']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/events', [
                'title' => 'Bad',
                'description' => 'd',
                'start_date' => '2026-11-10T14:30:00',
                'end_date' => '2026-11-01T14:30:00',
                'location' => 'PP',
                'fee_type' => 'paid',
                'status' => 'weird',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date', 'fee_amount', 'status']);
    }

    public function test_admin_can_create_draft_and_draft_is_hidden_from_public_feed(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/events', [
                'title' => 'Hidden Event',
                'description' => 'Not yet visible.',
                'start_date' => '2026-12-01T09:00:00',
                'location' => 'Online',
                'fee_type' => 'free',
                'status' => 'draft',
            ])
            ->assertCreated()
            ->assertJsonPath('event.status', 'draft');

        $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonMissingPath('events.*.title', 'Hidden Event');

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/events/{$response->json('event.id')}")
            ->assertOk()
            ->assertJsonPath('event.status', 'draft');
    }

    public function test_admin_can_upload_cover_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->post('/api/v1/admin/events', [
                'title' => 'Covered Event',
                'description' => 'Has a cover.',
                'start_date' => '2026-12-01T09:00:00',
                'location' => 'Online',
                'fee_type' => 'free',
                'status' => 'draft',
                'cover_image' => UploadedFile::fake()->image('cover.jpg'),
            ])
            ->assertCreated();

        $this->assertMatchesRegularExpression('#^event-covers/.+\.jpg$#', $response->json('event.cover_image'));
        Storage::disk('public')->assertExists($response->json('event.cover_image'));
    }

    public function test_admin_can_publish_unpublish_cancel_complete_and_set_featured(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->draft()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/events/{$event->id}", ['status' => 'published'])
            ->assertOk()
            ->assertJsonPath('event.status', 'published');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/events/{$event->id}", ['status' => 'cancelled'])
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/events/{$event->id}", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('event.status', 'completed');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/events/{$event->id}", ['is_featured' => true])
            ->assertOk()
            ->assertJsonPath('event.is_featured', true);

        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => 'completed', 'is_featured' => true]);
    }

    public function test_admin_can_remove_cover_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $event = Event::factory()->create(['cover_image' => 'event-covers/old.jpg']);
        Storage::disk('public')->put('event-covers/old.jpg', 'fake');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/events/{$event->id}", ['remove_cover_image' => true])
            ->assertOk()
            ->assertJsonPath('event.cover_image', null);

        Storage::disk('public')->assertMissing('event-covers/old.jpg');
    }

    public function test_admin_can_delete_event(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $event = Event::factory()->create(['cover_image' => 'event-covers/delete-me.jpg']);
        Storage::disk('public')->put('event-covers/delete-me.jpg', 'fake');
        EventRegistration::create(['event_id' => $event->id, 'user_id' => User::factory()->create()->id]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/events/{$event->id}")
            ->assertOk();

        Storage::disk('public')->assertMissing('event-covers/delete-me.jpg');
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
        $this->assertDatabaseMissing('event_registrations', ['event_id' => $event->id]);
    }

    // --- Registration ---

    public function test_guest_cannot_register_for_event(): void
    {
        $event = Event::factory()->published()->create();

        $this->postJson("/api/v1/events/{$event->id}/register")->assertUnauthorized();
    }

    public function test_member_can_register_for_published_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->published()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->id}/register")
            ->assertCreated()
            ->assertJsonPath('registration.event_id', $event->id)
            ->assertJsonPath('registration.user_id', $user->id);

        $this->assertDatabaseHas('event_registrations', ['event_id' => $event->id, 'user_id' => $user->id]);
    }

    public function test_member_cannot_register_twice(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->published()->create();
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->id}/register")
            ->assertStatus(409);
    }

    public function test_event_with_full_capacity_rejects_registration(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->published()->create(['max_participants' => 1]);
        EventRegistration::create(['event_id' => $event->id, 'user_id' => User::factory()->create()->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->id}/register")
            ->assertUnprocessable();
    }

    public function test_non_published_event_rejects_registration(): void
    {
        $user = User::factory()->create();
        $draft = Event::factory()->draft()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$draft->id}/register")
            ->assertUnprocessable();
    }

    public function test_admin_can_list_and_remove_registrations(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->published()->create();
        $registrant = User::factory()->create(['name' => 'Sokha Test']);
        $registration = EventRegistration::create(['event_id' => $event->id, 'user_id' => $registrant->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/events/{$event->id}/registrations")
            ->assertOk()
            ->assertJsonCount(1, 'registrations.data')
            ->assertJsonPath('registrations.data.0.user.name', 'Sokha Test');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/events/{$event->id}/registrations/{$registration->id}")
            ->assertOk();

        $this->assertDatabaseMissing('event_registrations', ['id' => $registration->id]);
    }
}
