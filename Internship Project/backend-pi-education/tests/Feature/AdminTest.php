<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'stats' => ['users', 'books', 'categories', 'courses', 'total_revenue'],
                'revenue_by_month',
            ]);
    }

    public function test_reader_cannot_access_admin(): void
    {
        $reader = User::factory()->create();

        $this->actingAs($reader, 'sanctum')
            ->getJson('/api/v1/admin/dashboard')
            ->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_admin(): void
    {
        $this->getJson('/api/v1/admin/dashboard')
            ->assertStatus(401);
    }

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->admin()->create();
        $created = User::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonCount($created->count() + 1, 'data'); // admin + 3 created
    }

    public function test_admin_can_change_user_role(): void
    {
        $admin = User::factory()->admin()->create();
        $reader = User::factory()->create(['role' => 'reader']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/users/{$reader->id}/role", ['role' => 'admin'])
            ->assertOk()
            ->assertJsonPath('user.role', 'admin');

        $reader->refresh();
        $this->assertEquals('admin', $reader->role);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/users/{$admin->id}/role", ['role' => 'reader'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot change your own role.');
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('message', 'User deleted.');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/users/{$admin->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot delete your own account.');
    }

    public function test_admin_can_list_payments(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $book = Book::factory()->create(['price' => 5.00, 'is_free' => false, 'is_published' => true]);

        Payment::create([
            'user_id' => $user->id,
            'reference' => 'TEST-PAY-001',
            'amount' => 5.00,
            'status' => 'completed',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/payments')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_list_sharing_keys(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/sharing-keys')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_admin_can_search_users(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Alice Smith']);
        User::factory()->create(['name' => 'Bob Smith']);
        User::factory()->create(['name' => 'Charlie Brown']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users?search=Smith')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_manage_categories(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/categories', ['name' => 'Admin Created'])
            ->assertStatus(201)
            ->assertJsonPath('category.name', 'Admin Created');
    }

    public function test_non_admin_cannot_manage_categories(): void
    {
        $reader = User::factory()->create();

        $this->actingAs($reader, 'sanctum')
            ->postJson('/api/v1/admin/categories', ['name' => 'Unauthorized'])
            ->assertStatus(403);
    }

    public function test_dashboard_counts_are_accurate(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(5)->create();
        Book::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('stats.users', User::count())
            ->assertJsonPath('stats.books', 3);
    }
}
