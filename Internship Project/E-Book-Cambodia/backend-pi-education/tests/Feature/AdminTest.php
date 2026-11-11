<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
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
        $book = Book::factory()->create([
            'price' => 5.00,
            'is_free' => false,
            'is_published' => true,
            'view_count' => 31,
        ]);
        $purchase = Purchase::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => 5.00,
            'status' => Purchase::STATUS_PAID,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'purchase_id' => $purchase->id,
            'reference' => 'TEST-PAY-001',
            'amount' => 5.00,
            'status' => 'completed',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/payments')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.purchase.book.view_count', 31);
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
        $admin = User::factory()->admin()->create(['name' => 'Zara Admin']);
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

    // --- Admin book upload ---

    /**
     * A payload mirroring the real admin book form field for field, so a bad
     * rule on any field the UI actually sends is caught here.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function adminBookPayload(array $overrides = []): array
    {
        return array_merge([
            'category_id' => Category::factory()->create()->id,
            'title' => 'An Admin Uploaded Book',
            'description' => 'A description long enough to satisfy validation.',
            'content' => 'Chapter one.',
            'author' => 'Admin Author',
            'language' => 'km',
            'price' => 5,
            'is_free' => false,
            'is_published' => true,
            'is_vip_only' => false,
            'is_coming_soon' => false,
            'audience' => 'general',
            'publisher' => 'PI Education',
            'subject' => 'Mathematics',
            'education_level' => 'Lower Secondary',
            'grade' => 'Grade 8',
            'publication_date' => now()->subYear()->toDateString(),
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
            'file' => UploadedFile::fake()->createWithContent('book.pdf', $this->fakePdf(4)),
        ], $overrides);
    }

    public function test_admin_can_upload_a_book_and_publish_it_directly(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->post('/api/v1/admin/books', $this->adminBookPayload())
            ->assertStatus(201)
            ->assertJsonPath('book.title', 'An Admin Uploaded Book')
            // The admin form's publish checkbox is a legitimate shortcut past
            // the review queue, so it has to land on `published`.
            ->assertJsonPath('book.status', Book::STATUS_PUBLISHED)
            ->assertJsonPath('book.is_published', true);

        $this->assertDatabaseHas('books', [
            'title' => 'An Admin Uploaded Book',
            'status' => Book::STATUS_PUBLISHED,
        ]);
    }

    public function test_admin_upload_defaults_to_a_draft_when_not_published(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->post('/api/v1/admin/books', $this->adminBookPayload(['is_published' => false]))
            ->assertStatus(201)
            ->assertJsonPath('book.status', Book::STATUS_DRAFT)
            ->assertJsonPath('book.is_published', false);
    }

    /**
     * The book document and cover are subject to the configured upload limits
     * (config/books.php), so an oversized file must be rejected with a 422
     * before it ever reaches the storage service.
     */
    public function test_admin_book_upload_rejects_file_over_limit(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $admin = User::factory()->admin()->create();

        $original = config('books.max_file_kilobytes');
        Config::set('books.max_file_kilobytes', 1);

        try {
            // A valid-named PDF whose content clearly exceeds 1 KB.
            $oversized = UploadedFile::fake()->createWithContent(
                'book.pdf',
                str_repeat($this->fakePdf(4), 4)
            );

            $this->actingAs($admin, 'sanctum')
                ->post('/api/v1/admin/books', $this->adminBookPayload(['file' => $oversized]))
                ->assertStatus(422)
                ->assertJsonValidationErrors('file');

            $this->assertDatabaseMissing('books', ['title' => 'An Admin Uploaded Book']);
        } finally {
            Config::set('books.max_file_kilobytes', $original);
        }
    }

    public function test_admin_book_upload_rejects_cover_over_limit(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $admin = User::factory()->admin()->create();

        $original = config('books.max_cover_kilobytes');
        Config::set('books.max_cover_kilobytes', 1);

        try {
            $oversizedCover = UploadedFile::fake()->createWithContent('cover.jpg', str_repeat('A', 2048));

            $this->actingAs($admin, 'sanctum')
                ->post('/api/v1/admin/books', $this->adminBookPayload(['cover_image' => $oversizedCover]))
                ->assertStatus(422)
                ->assertJsonValidationErrors('cover_image');

            $this->assertDatabaseMissing('books', ['title' => 'An Admin Uploaded Book']);
        } finally {
            Config::set('books.max_cover_kilobytes', $original);
        }
    }

    /**
     * Un-publishing a live book through the admin form must not discard the
     * fact that it already passed review.
     */
    public function test_admin_unpublishing_a_book_keeps_it_approved(): void
    {
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->published()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/books/{$book->id}", ['is_published' => false])
            ->assertOk()
            ->assertJsonPath('book.status', Book::STATUS_APPROVED)
            ->assertJsonPath('book.is_published', false);
    }

    public function test_admin_can_set_free_preview_pages_that_gate_reading(): void
    {
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->published()->create([
            'is_free' => false,
            'price' => 5.00,
            'preview_pages' => 0,
            'preview_mode' => Book::PREVIEW_MODE_NONE,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/books/{$book->id}", [
                'preview_pages' => 3,
                'preview_mode' => Book::PREVIEW_MODE_PREVIEW,
            ])
            ->assertOk()
            ->assertJsonPath('book.preview_pages', 3)
            ->assertJsonPath('book.preview_mode', Book::PREVIEW_MODE_PREVIEW);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'preview_pages' => 3,
            'preview_mode' => Book::PREVIEW_MODE_PREVIEW,
        ]);

        // A guest now gets a preview scope limited to the first 3 pages.
        $this->getJson("/api/v1/reader/{$book->id}")
            ->assertOk()
            ->assertJsonPath('access.mode', 'preview')
            ->assertJsonPath('access.preview_pages', 3)
            ->assertJsonPath('access.preview_mode', Book::PREVIEW_MODE_PREVIEW);

        // Zero removes the gate, turning a non-buyer away entirely.
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/books/{$book->id}", ['preview_pages' => 0])
            ->assertOk();

        $this->getJson("/api/v1/reader/{$book->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_gate_a_book_with_readtest_mode(): void
    {
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->published()->create([
            'is_free' => false,
            'price' => 5.00,
            'preview_pages' => 0,
            'preview_mode' => Book::PREVIEW_MODE_NONE,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/books/{$book->id}", [
                'preview_pages' => 5,
                'preview_mode' => Book::PREVIEW_MODE_READTEST,
            ])
            ->assertOk()
            ->assertJsonPath('book.preview_mode', Book::PREVIEW_MODE_READTEST);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'preview_pages' => 5,
            'preview_mode' => Book::PREVIEW_MODE_READTEST,
        ]);

        // Readtest behaves like a preview for access purposes but reports its
        // own mode so the reader can label the paywall correctly.
        $this->getJson("/api/v1/reader/{$book->id}")
            ->assertOk()
            ->assertJsonPath('access.mode', 'preview')
            ->assertJsonPath('access.preview_mode', Book::PREVIEW_MODE_READTEST)
            ->assertJsonPath('access.preview_pages', 5);
    }

    public function test_non_admin_cannot_upload_a_book(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $reader = User::factory()->create();

        $this->actingAs($reader, 'sanctum')
            ->post('/api/v1/admin/books', $this->adminBookPayload())
            ->assertStatus(403);

        $this->assertDatabaseMissing('books', ['title' => 'An Admin Uploaded Book']);
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
