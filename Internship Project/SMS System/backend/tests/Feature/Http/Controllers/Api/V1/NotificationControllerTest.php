<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;
    use SeedsRolesAndPermissions;

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/my/notifications')
            ->assertUnauthorized();
    }

    public function test_index_returns_only_the_authenticated_users_notifications(): void
    {
        $this->seedRolesAndPermissions();
        $me = $this->adminUser();
        $other = $this->staffUser();
        Notification::factory()->for($me, 'user')->create(['title' => 'Mine One']);
        Notification::factory()->for($me, 'user')->create(['title' => 'Mine Two']);
        Notification::factory()->for($other, 'user')->create(['title' => 'Theirs']);

        $this->actingAs($me, 'sanctum')
            ->getJson('/api/v1/my/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['title' => 'Theirs'])
            ->assertJsonStructure(['meta' => ['pagination']]);
    }

    public function test_index_filters_to_unread_notifications_when_requested(): void
    {
        $this->seedRolesAndPermissions();
        $me = $this->adminUser();
        Notification::factory()->for($me, 'user')->create(['title' => 'Unread One']);
        Notification::factory()->for($me, 'user')->read()->create(['title' => 'Read One']);

        $this->actingAs($me, 'sanctum')
            ->getJson('/api/v1/my/notifications?unread_only=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Unread One');
    }

    public function test_unread_count_counts_only_the_users_unread_notifications(): void
    {
        $this->seedRolesAndPermissions();
        $me = $this->adminUser();
        $other = $this->staffUser();
        Notification::factory()->for($me, 'user')->count(2)->create();
        Notification::factory()->for($me, 'user')->read()->create();
        Notification::factory()->for($other, 'user')->create();

        $this->actingAs($me, 'sanctum')
            ->getJson('/api/v1/my/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 2);
    }

    public function test_show_returns_404_for_a_missing_notification(): void
    {
        $this->seedRolesAndPermissions();
        $me = $this->adminUser();

        $this->actingAs($me, 'sanctum')
            ->getJson('/api/v1/notifications/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    }

    public function test_show_returns_403_for_another_users_notification(): void
    {
        $this->seedRolesAndPermissions();
        $me = $this->adminUser();
        $other = $this->staffUser();
        $notification = Notification::factory()->for($other, 'user')->create();

        $this->actingAs($me, 'sanctum')
            ->getJson("/api/v1/notifications/{$notification->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'This notification belongs to another user.');
    }

    public function test_show_marks_an_unread_notification_as_read(): void
    {
        $this->seedRolesAndPermissions();
        $me = $this->adminUser();
        $notification = Notification::factory()->for($me, 'user')->create(['title' => 'Read On View']);

        $response = $this->actingAs($me, 'sanctum')
            ->getJson("/api/v1/notifications/{$notification->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Read On View');

        $this->assertNotNull($response->json('data.read_at'));
        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_mark_read_returns_403_for_another_users_notification(): void
    {
        $this->seedRolesAndPermissions();
        $me = $this->adminUser();
        $other = $this->staffUser();
        $notification = Notification::factory()->for($other, 'user')->create();

        $this->actingAs($me, 'sanctum')
            ->putJson("/api/v1/notifications/{$notification->id}/read")
            ->assertForbidden();
    }

    public function test_mark_read_marks_a_single_notification_as_read(): void
    {
        $this->seedRolesAndPermissions();
        $me = $this->adminUser();
        $notification = Notification::factory()->for($me, 'user')->create();

        $response = $this->actingAs($me, 'sanctum')
            ->putJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk();

        $this->assertNotNull($response->json('data.read_at'));
        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_mark_all_read_marks_every_owned_notification_as_read(): void
    {
        $this->seedRolesAndPermissions();
        $me = $this->adminUser();
        $other = $this->staffUser();
        Notification::factory()->for($me, 'user')->count(2)->create();
        Notification::factory()->for($me, 'user')->read()->create();
        $others = Notification::factory()->for($other, 'user')->create();

        $this->actingAs($me, 'sanctum')
            ->putJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.message', 'All notifications marked as read.');

        $this->assertSame(3, Notification::query()->where('user_id', $me->id)->whereNotNull('read_at')->count());
        $this->assertNull($others->refresh()->read_at);
    }

    public function test_destroy_deletes_the_owned_notification(): void
    {
        $this->seedRolesAndPermissions();
        $me = $this->adminUser();
        $notification = Notification::factory()->for($me, 'user')->create();

        $this->actingAs($me, 'sanctum')
            ->deleteJson("/api/v1/notifications/{$notification->id}")
            ->assertOk()
            ->assertJsonPath('data.message', 'Notification deleted.');

        $this->assertModelMissing($notification);
    }

    public function test_destroy_returns_403_for_another_users_notification(): void
    {
        $this->seedRolesAndPermissions();
        $me = $this->adminUser();
        $other = $this->staffUser();
        $notification = Notification::factory()->for($other, 'user')->create();

        $this->actingAs($me, 'sanctum')
            ->deleteJson("/api/v1/notifications/{$notification->id}")
            ->assertForbidden();
    }
}
