<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Purchase;
use App\Models\User;
use App\Models\VipPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VipFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_lists_active_vip_plans(): void
    {
        VipPlan::create([
            'name' => 'Monthly',
            'slug' => 'vip-monthly',
            'price' => 7.99,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/vip-plans')
            ->assertOk()
            ->assertJsonPath('plans.0.slug', 'vip-monthly');
    }

    public function test_mock_vip_purchase_activates_membership(): void
    {
        $user = User::factory()->create();
        $plan = VipPlan::create([
            'name' => 'Monthly',
            'slug' => 'vip-monthly',
            'price' => 7.99,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/vip/{$plan->id}/purchase", [
                'method' => 'mock',
                'mock_approve' => true,
            ])
            ->assertOk()
            ->assertJsonPath('activated', true);

        $this->assertTrue($user->fresh()->isVip());
        $this->assertNotNull($user->fresh()->vip_until);
        $this->assertEquals('vip-monthly', $user->fresh()->vip_plan);
    }

    public function test_vip_member_can_read_paid_book_without_purchase(): void
    {
        $user = User::factory()->create([
            'vip_until' => now()->addDays(30),
            'vip_plan' => 'vip-monthly',
        ]);
        $book = Book::factory()->create(['is_free' => false]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertOk()
            ->assertJsonPath('book.id', $book->id);
    }

    public function test_vip_member_sees_all_eligible_books_in_library(): void
    {
        $user = User::factory()->create([
            'vip_until' => now()->addDays(30),
            'vip_plan' => 'vip-monthly',
        ]);
        Book::factory()->count(3)->create(['is_free' => false]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/library')
            ->assertOk();

        $this->assertCount(3, $response->json('vip'));
    }

    public function test_fresh_purchase_extends_existing_membership(): void
    {
        $user = User::factory()->create([
            'vip_until' => now()->addDays(10),
            'vip_plan' => 'vip-monthly',
        ]);
        $plan = VipPlan::create([
            'name' => 'Monthly',
            'slug' => 'vip-monthly',
            'price' => 7.99,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/vip/{$plan->id}/purchase", [
                'method' => 'mock',
                'mock_approve' => true,
            ])
            ->assertOk();

        $expected = $user->fresh()->vip_until;
        $this->assertTrue($expected->isAfter(now()->addDays(35)));
    }

    public function test_manual_method_requires_admin_approval(): void
    {
        $user = User::factory()->create();
        $plan = VipPlan::create([
            'name' => 'Monthly',
            'slug' => 'vip-monthly',
            'price' => 7.99,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/vip/{$plan->id}/purchase", [
                'method' => 'wing',
            ])
            ->assertStatus(202);

        $purchase = Purchase::where('user_id', $user->id)->firstOrFail();
        $this->assertFalse($user->fresh()->isVip());

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/payments/{$response->json('payment.id')}/approve")
            ->assertOk();

        $this->assertTrue($user->fresh()->isVip());
        $this->assertEquals(Purchase::STATUS_PAID, $purchase->fresh()->status);
    }

    public function test_inactive_plan_is_not_purchasable(): void
    {
        $user = User::factory()->create();
        $plan = VipPlan::create([
            'name' => 'Retired',
            'slug' => 'vip-retired',
            'price' => 5,
            'duration_days' => 7,
            'is_active' => false,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/vip/{$plan->id}/purchase", [
                'method' => 'mock',
                'mock_approve' => true,
            ])
            ->assertStatus(404);
    }

    public function test_is_vip_is_serialized_on_me(): void
    {
        $user = User::factory()->create([
            'vip_until' => now()->addDays(30),
            'vip_plan' => 'vip-monthly',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('user.is_vip', true)
            ->assertJsonPath('user.vip_plan', 'vip-monthly');
    }
}
