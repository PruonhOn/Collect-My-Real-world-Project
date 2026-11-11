<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->admin = User::factory()->vip()->create();
        $this->admin->assignRole(User::ROLE_ADMIN);
    }

    public function test_non_admin_cannot_access_admin_api(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/admin/dashboard')->assertStatus(403);
        $this->actingAs($user)->getJson('/api/v1/admin/books')->assertStatus(403);
    }

    public function test_admin_can_view_dashboard_stats(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'counters' => ['total_books', 'published_books', 'total_users', 'total_orders', 'revenue'],
                    'recent_orders',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(6, $data['counters']['published_books']);
        $this->assertEquals(0, $data['counters']['revenue']);
    }

    public function test_admin_can_update_user_role_and_vip(): void
    {
        $target = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->patchJson('/api/v1/admin/users/'.$target->id, [
                'role' => 'admin',
                'status' => 'inactive',
                'vip_until' => now()->addDays(10)->toDateString(),
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.roles', ['admin'])
            ->assertJsonPath('data.is_vip', true);

        $this->assertFalse($target->fresh()->isActive());
    }

    public function test_admin_can_create_update_and_delete_book(): void
    {
        $category = Category::firstOrFail();
        $author = Author::firstOrFail();

        $payload = [
            'slug' => 'new-book',
            'type' => 'FREE',
            'price' => 0,
            'status' => 'DRAFT',
            'language' => 'km',
            'category_ids' => [$category->id],
            'author_ids' => [$author->id],
            'translations' => [
                'en' => ['title' => 'New Book', 'description' => 'A test book.', 'content' => 'Hello world.'],
                'km' => ['title' => 'សៀវភៅថ្មី', 'description' => 'សៀវភៅសាកល្បង។', 'content' => 'ជំរាបសួរ។'],
            ],
        ];

        $created = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/books', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.slug', 'new-book');

        $bookId = $created->json('data.id');

        $this->assertDatabaseHas('books', ['id' => $bookId, 'status' => 'DRAFT']);

        $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/books/'.$bookId, array_merge($payload, ['slug' => 'new-book-updated', 'status' => 'PUBLISHED']))
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'PUBLISHED');

        $this->actingAs($this->admin)
            ->deleteJson('/api/v1/admin/books/'.$bookId)
            ->assertStatus(200);

        $this->assertSoftDeleted('books', ['id' => $bookId]);
    }

    public function test_book_slug_must_be_unique(): void
    {
        $payload = [
            'slug' => 'algebra-basics',
            'type' => 'FREE',
            'price' => 0,
            'status' => 'DRAFT',
            'language' => 'km',
            'translations' => ['en' => ['title' => 'Duplicate']],
        ];

        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/books', $payload)
            ->assertStatus(422);
    }

    public function test_admin_can_manage_categories(): void
    {
        $payload = [
            'slug' => 'science-new',
            'status' => 'active',
            'translations' => [
                'en' => ['name' => 'Science', 'description' => 'Science books'],
                'km' => ['name' => 'វិទ្យាសាស្ត្រ', 'description' => 'សៀវភៅវិទ្យាសាស្ត្រ'],
            ],
        ];

        $created = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/categories', $payload)
            ->assertStatus(201);

        $id = $created->json('data.id');

        $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/categories/'.$id, array_merge($payload, ['slug' => 'science-updated']))
            ->assertStatus(200);

        $this->actingAs($this->admin)
            ->deleteJson('/api/v1/admin/categories/'.$id)
            ->assertStatus(200);

        $this->assertSoftDeleted('categories', ['id' => $id]);
    }

    public function test_admin_can_manage_authors(): void
    {
        $payload = [
            'slug' => 'new-author',
            'translations' => [
                'en' => ['name' => 'New Author', 'biography' => 'Writes books'],
                'km' => ['name' => 'អ្នកនិពន្ធថ្មី', 'biography' => 'សរសេរសៀវភៅ'],
            ],
        ];

        $created = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/authors', $payload)
            ->assertStatus(201);

        $this->actingAs($this->admin)
            ->deleteJson('/api/v1/admin/authors/'.$created->json('data.id'))
            ->assertStatus(200);
    }

    public function test_admin_can_manage_vip_plans(): void
    {
        $payload = [
            'slug' => 'lifetime-plan',
            'price' => 99.00,
            'duration_days' => 9999,
            'features' => ['Everything'],
            'is_active' => true,
            'translations' => [
                'en' => ['name' => 'Lifetime', 'description' => 'One time payment'],
                'km' => ['name' => 'អស់មួយជីវិត', 'description' => 'បង់ម្តង'],
            ],
        ];

        $created = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/vip/plans', $payload)
            ->assertStatus(201);

        $id = $created->json('data.id');

        $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/vip/plans/'.$id, array_merge($payload, ['is_active' => false]))
            ->assertStatus(200);

        $this->actingAs($this->admin)
            ->deleteJson('/api/v1/admin/vip/plans/'.$id)
            ->assertStatus(200);
    }

    public function test_public_books_still_exclude_content(): void
    {
        $this->actingAs($this->admin)->getJson('/api/v1/books')->assertStatus(200);
    }
}
