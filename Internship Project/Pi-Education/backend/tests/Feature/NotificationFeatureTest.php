<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Notification;
use App\Models\Review;
use App\Models\User;
use App\Models\VipPlan;
use App\Models\VipSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_paying_an_order_creates_notification(): void
    {
        $user = User::factory()->create();
        $paid = Book::where('type', 'PAID')->firstOrFail();

        $order = $this->actingAs($user)
            ->postJson('/api/v1/orders', ['book_ids' => [$paid->id]])
            ->json('data');

        $this->actingAs($user)
            ->postJson("/api/v1/orders/{$order['order_number']}/pay", ['payment_method' => 'mock'])
            ->assertStatus(200);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => Notification::TYPE_ORDER_PAID,
        ]);
    }

    public function test_subscribing_to_vip_creates_notification(): void
    {
        $user = User::factory()->create();
        $plan = VipPlan::query()->where('slug', 'yearly')->firstOrFail();

        $this->actingAs($user)
            ->postJson('/api/v1/vip/subscribe', ['plan_id' => $plan->id])
            ->assertStatus(201);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => Notification::TYPE_VIP_SUBSCRIBED,
        ]);

        $this->assertDatabaseHas('vip_subscriptions', [
            'user_id' => $user->id,
            'status' => VipSubscription::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_approving_review_notifies_reviewer(): void
    {
        $admin = User::where('email', 'admin@pieducation.test')->firstOrFail();
        $book = Book::query()->where('status', Book::STATUS_PUBLISHED)->where('type', 'FREE')->firstOrFail();
        $reviewer = User::factory()->create();
        $review = Review::factory()->pending()->create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($admin)
            ->patchJson('/api/v1/admin/reviews/'.$review->id, ['status' => Review::STATUS_APPROVED])
            ->assertStatus(200);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $reviewer->id,
            'type' => Notification::TYPE_REVIEW_APPROVED,
        ]);
    }

    public function test_user_sees_only_own_notifications(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Notification::factory()->count(2)->create(['user_id' => $user->id]);
        Notification::factory()->count(3)->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.notifications')
            ->assertJsonPath('data.pagination.total', 2);
    }

    public function test_unread_count_is_reported(): void
    {
        $user = User::factory()->create();

        Notification::factory()->count(2)->create(['user_id' => $user->id]);
        Notification::factory()->read()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/notifications/unread-count')
            ->assertStatus(200)
            ->assertJsonPath('data.unread_count', 2);
    }

    public function test_guest_cannot_access_notifications(): void
    {
        $this->getJson('/api/v1/notifications')->assertStatus(401);
    }

    public function test_marking_single_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patchJson('/api/v1/notifications/'.$notification->id.'/read')
            ->assertStatus(200);

        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'read_at' => now()]);
    }

    public function test_marking_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(3)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson('/api/v1/notifications/read-all')
            ->assertStatus(200);

        $this->assertDatabaseMissing('notifications', ['user_id' => $user->id, 'read_at' => null]);
    }

    public function test_guests_cannot_read_others_notifications(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->patchJson('/api/v1/notifications/'.$notification->id.'/read')
            ->assertStatus(404);
    }
}
