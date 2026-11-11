<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function makeCategory(): Category
    {
        return Category::create(['name' => 'Fiction', 'slug' => 'fiction-author-test']);
    }

    /**
     * A complete, valid author book submission, mirroring the real form field
     * for field so a bad rule on any field the UI sends is exercised here.
     * Extra fields can be layered on to assert that a column is refused.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validBookPayload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $category->id,
            'title' => 'My New Book',
            'description' => 'A thorough description of the book being submitted.',
            'content' => 'Chapter one.',
            'language' => 'km',
            'price' => 5,
            'is_free' => false,
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
            'tags' => ['Mathematics', 'Grade 10'],
        ], $overrides);
    }

    // --- Login ---

    public function test_author_can_login(): void
    {
        $author = Author::factory()->create(['password' => 'password']);

        $this->postJson('/api/v1/author/login', [
            'email' => $author->email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('author.email', $author->email)
            ->assertJsonPath('author.status', 'active')
            ->assertJsonStructure(['token', 'token_type', 'author']);
    }

    public function test_login_does_not_expose_password(): void
    {
        $author = Author::factory()->create(['password' => 'password']);

        $response = $this->postJson('/api/v1/author/login', [
            'email' => $author->email,
            'password' => 'password',
        ])->assertOk();

        $this->assertArrayNotHasKey('password', $response->json('author'));
        $this->assertArrayNotHasKey('password', $response->json());
    }

    public function test_login_tracks_last_login_at(): void
    {
        $author = Author::factory()->create(['password' => 'password']);

        $this->postJson('/api/v1/author/login', [
            'email' => $author->email,
            'password' => 'password',
        ])->assertOk();

        $this->assertNotNull($author->fresh()->last_login_at);
    }

    public function test_author_cannot_login_with_wrong_password(): void
    {
        $author = Author::factory()->create(['password' => 'password']);

        $this->postJson('/api/v1/author/login', [
            'email' => $author->email,
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    public function test_suspended_author_cannot_login(): void
    {
        $author = Author::factory()->suspended()->create(['password' => 'password']);

        $this->postJson('/api/v1/author/login', [
            'email' => $author->email,
            'password' => 'password',
        ])->assertStatus(403);
    }

    public function test_reader_email_cannot_login_as_author(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->postJson('/api/v1/author/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(401);
    }

    // --- Profile ---

    public function test_author_can_fetch_profile(): void
    {
        $author = Author::factory()->create();

        $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/author/me')
            ->assertOk()
            ->assertJsonPath('author.id', $author->id)
            ->assertJsonPath('author.email', $author->email);
    }

    public function test_author_profile_never_exposes_password(): void
    {
        $author = Author::factory()->create();

        $response = $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/author/me')
            ->assertOk();

        $this->assertArrayNotHasKey('password', $response->json('author'));
    }

    public function test_author_can_update_profile(): void
    {
        $author = Author::factory()->create();

        $this->actingAs($author, 'sanctum')
            ->putJson('/api/v1/author/me', [
                'name' => 'Updated Name',
                'phone' => '098765432',
                'bio' => 'Hello world',
                'specialization' => 'Poetry',
                'organization' => 'Org',
                'school' => 'School',
                'social_links' => ['facebook' => 'https://facebook.com/author'],
            ])
            ->assertOk()
            ->assertJsonPath('author.name', 'Updated Name')
            ->assertJsonPath('author.phone', '098765432')
            ->assertJsonPath('author.specialization', 'Poetry')
            ->assertJsonPath('author.social_links.facebook', 'https://facebook.com/author');
    }

    public function test_author_profile_accepts_json_string_social_links(): void
    {
        $author = Author::factory()->create();

        $this->actingAs($author, 'sanctum')
            ->putJson('/api/v1/author/me', [
                'name' => 'Json Links Author',
                'social_links' => '{"youtube": "https://youtube.com/author", "facebook": "https://facebook.com/author"}',
            ])
            ->assertOk()
            ->assertJsonPath('author.social_links.youtube', 'https://youtube.com/author');

        $this->actingAs($author, 'sanctum')
            ->putJson('/api/v1/author/me', [
                'social_links' => 'not valid json {',
            ])
            ->assertStatus(422);
    }

    public function test_author_can_change_password(): void
    {
        $author = Author::factory()->create(['password' => 'password']);

        $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/author/change-password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertOk();

        $this->postJson('/api/v1/author/login', [
            'email' => $author->email,
            'password' => 'new-password',
        ])->assertOk();
    }

    // --- Author books ---

    public function test_author_can_create_book_as_a_draft(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $author = Author::factory()->create();
        $category = $this->makeCategory();

        $this->actingAs($author, 'sanctum')
            ->post('/api/v1/author/books', $this->validBookPayload($category))
            ->assertStatus(201)
            ->assertJsonPath('book.title', 'My New Book')
            ->assertJsonPath('book.author_id', $author->id)
            ->assertJsonPath('book.status', Book::STATUS_DRAFT)
            ->assertJsonPath('book.is_published', false);

        $this->assertDatabaseHas('books', [
            'author_id' => $author->id,
            'author' => $author->name,
            'status' => Book::STATUS_DRAFT,
            'is_published' => false,
        ]);
    }

    /**
     * The book document and cover are subject to the configured upload limits
     * (config/books.php), so an oversized file must be rejected with a 422
     * before it ever reaches the storage service.
     */
    public function test_author_book_upload_rejects_file_over_limit(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $author = Author::factory()->create();
        $category = $this->makeCategory();

        $original = config('books.max_file_kilobytes');
        Config::set('books.max_file_kilobytes', 1);

        try {
            $oversized = UploadedFile::fake()->createWithContent(
                'book.pdf',
                str_repeat($this->fakePdf(4), 4)
            );

            $this->actingAs($author, 'sanctum')
                ->post('/api/v1/author/books', $this->validBookPayload($category, ['file' => $oversized]))
                ->assertStatus(422)
                ->assertJsonValidationErrors('file');

            $this->assertDatabaseMissing('books', ['author_id' => $author->id]);
        } finally {
            Config::set('books.max_file_kilobytes', $original);
        }
    }

    public function test_author_book_upload_rejects_cover_over_limit(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $author = Author::factory()->create();
        $category = $this->makeCategory();

        $original = config('books.max_cover_kilobytes');
        Config::set('books.max_cover_kilobytes', 1);

        try {
            $oversizedCover = UploadedFile::fake()->createWithContent('cover.jpg', str_repeat('A', 2048));

            $this->actingAs($author, 'sanctum')
                ->post('/api/v1/author/books', $this->validBookPayload($category, ['cover_image' => $oversizedCover]))
                ->assertStatus(422)
                ->assertJsonValidationErrors('cover_image');

            $this->assertDatabaseMissing('books', ['author_id' => $author->id]);
        } finally {
            Config::set('books.max_cover_kilobytes', $original);
        }
    }

    /**
     * An author must not be able to publish, nor to fake the review trail, by
     * smuggling those fields into the book form.
     */
    public function test_author_cannot_publish_a_book_by_setting_workflow_fields(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $author = Author::factory()->create();
        $category = $this->makeCategory();

        $this->actingAs($author, 'sanctum')
            ->post('/api/v1/author/books', $this->validBookPayload($category, [
                'is_published' => true,
                'status' => Book::STATUS_PUBLISHED,
                'review_note' => 'looks good to me',
                'published_at' => now()->toDateString(),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['is_published', 'status', 'review_note', 'published_at']);

        $this->assertDatabaseMissing('books', ['author_id' => $author->id]);
    }

    public function test_author_can_list_own_books(): void
    {
        $author = Author::factory()->create();
        Book::factory()->count(3)->create(['author_id' => $author->id, 'author' => $author->name]);
        // Another author's book must not appear.
        Book::factory()->create(['author_id' => Author::factory()->create()->id]);

        $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/author/books')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_author_can_view_own_book(): void
    {
        $author = Author::factory()->create();
        $book = Book::factory()->create(['author_id' => $author->id, 'author' => $author->name]);

        $this->actingAs($author, 'sanctum')
            ->getJson("/api/v1/author/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('book.id', $book->id);
    }

    public function test_author_cannot_view_another_authors_book(): void
    {
        $author = Author::factory()->create();
        $other = Author::factory()->create();
        $book = Book::factory()->create(['author_id' => $other->id, 'author' => $other->name]);

        $this->actingAs($author, 'sanctum')
            ->getJson("/api/v1/author/books/{$book->id}")
            ->assertStatus(404);
    }

    public function test_author_can_update_own_draft_book(): void
    {
        $author = Author::factory()->create();
        $book = Book::factory()->draft()->create(['author_id' => $author->id, 'author' => $author->name]);

        $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/author/books/{$book->id}", ['title' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('book.title', 'Renamed');
    }

    public function test_author_can_edit_a_rejected_book_again(): void
    {
        $author = Author::factory()->create();
        $book = Book::factory()->rejected('Cover is too dark.')->create([
            'author_id' => $author->id,
            'author' => $author->name,
        ]);

        $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/author/books/{$book->id}", ['title' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('book.status', Book::STATUS_REJECTED);
    }

    /**
     * A submitted book is frozen: editing it behind the reviewer's back would
     * let the author change what is being reviewed.
     */
    public function test_author_cannot_update_a_book_under_review(): void
    {
        $author = Author::factory()->create();
        $book = Book::factory()->pending()->create(['author_id' => $author->id, 'author' => $author->name]);

        $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/author/books/{$book->id}", ['title' => 'Sneaky edit'])
            ->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => $book->title]);
    }

    public function test_author_cannot_update_a_published_book(): void
    {
        $author = Author::factory()->create();
        $book = Book::factory()->published()->create(['author_id' => $author->id, 'author' => $author->name]);

        $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/author/books/{$book->id}", ['title' => 'Sneaky edit'])
            ->assertForbidden();
    }

    public function test_author_cannot_update_another_authors_book(): void
    {
        $author = Author::factory()->create();
        $other = Author::factory()->create();
        $book = Book::factory()->create(['author_id' => $other->id, 'author' => $other->name]);

        $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/author/books/{$book->id}", ['title' => 'Hacked'])
            ->assertStatus(404);
    }

    public function test_author_can_delete_own_draft_book(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $author = Author::factory()->create();
        $book = Book::factory()->draft()->create(['author_id' => $author->id, 'author' => $author->name]);

        $this->actingAs($author, 'sanctum')
            ->deleteJson("/api/v1/author/books/{$book->id}")
            ->assertOk();

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    /**
     * A book that is under review or already live must never be deletable by
     * its author - the storefront and the review queue both depend on it.
     */
    public function test_author_cannot_delete_a_book_under_review_or_published(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $author = Author::factory()->create();
        $pending = Book::factory()->pending()->create(['author_id' => $author->id, 'author' => $author->name]);
        $published = Book::factory()->published()->create(['author_id' => $author->id, 'author' => $author->name]);

        $this->actingAs($author, 'sanctum')
            ->deleteJson("/api/v1/author/books/{$pending->id}")
            ->assertForbidden();

        $this->actingAs($author, 'sanctum')
            ->deleteJson("/api/v1/author/books/{$published->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $pending->id]);
        $this->assertDatabaseHas('books', ['id' => $published->id]);
    }

    public function test_author_stats_are_scoped_to_own_books(): void
    {
        $author = Author::factory()->create();
        $other = Author::factory()->create();

        $myBook = Book::factory()->create(['author_id' => $author->id, 'author' => $author->name, 'view_count' => 10]);
        $otherBook = Book::factory()->create(['author_id' => $other->id, 'author' => $other->name, 'view_count' => 99]);

        Purchase::create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $myBook->id,
            'amount' => 5.00,
            'status' => Purchase::STATUS_PAID,
        ]);

        $response = $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/author/stats')
            ->assertOk();

        $this->assertEquals(1, $response->json('stats.books'));
        $this->assertEquals(10, $response->json('stats.views'));
        $this->assertEquals(1, $response->json('stats.sold'));
        $this->assertEquals(5.0, (float) $response->json('stats.revenue'));
    }

    public function test_author_cannot_access_book_of_another_author_with_same_display_name(): void
    {
        $name = 'Shared Display Name';
        $author = Author::factory()->create(['name' => $name]);
        $other = Author::factory()->create(['name' => $name]);

        $book = Book::factory()->create([
            'author_id' => $other->id,
            'author' => $name,
        ]);

        $this->actingAs($author, 'sanctum')
            ->getJson("/api/v1/author/books/{$book->id}")
            ->assertStatus(404);

        $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/author/books/{$book->id}", ['title' => 'Hijacked'])
            ->assertStatus(404);

        $this->assertDatabaseHas('books', ['id' => $book->id, 'author_id' => $other->id, 'title' => $book->title]);

        $this->actingAs($author, 'sanctum')
            ->deleteJson("/api/v1/author/books/{$book->id}")
            ->assertStatus(404);

        $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/author/books')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_author_list_excludes_other_authors_books_when_display_name_collides(): void
    {
        $name = 'Colliding Name';
        $author = Author::factory()->create(['name' => $name]);
        $other = Author::factory()->create(['name' => $name]);

        $myBook = Book::factory()->create(['author_id' => $author->id, 'author' => $name]);
        Book::factory()->create(['author_id' => $other->id, 'author' => $name]);

        $response = $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/author/books')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertEquals($myBook->id, $response->json('data.0.id'));
    }

    // --- Isolation between Author, User and Admin ---

    public function test_author_cannot_access_admin_routes(): void
    {
        $author = Author::factory()->create();

        $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/admin/dashboard')
            ->assertStatus(403);
    }

    public function test_author_cannot_access_user_routes(): void
    {
        $author = Author::factory()->create();

        $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/me')
            ->assertStatus(403);

        $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/library')
            ->assertStatus(403);
    }

    public function test_member_cannot_access_author_routes(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/author/me')
            ->assertStatus(403);
    }

    // --- Admin management ---

    public function test_admin_can_create_author_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/authors', [
                'name' => 'New Author',
                'email' => 'new@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'phone' => '0123456',
                'organization' => 'Pi Org',
            ])
            ->assertStatus(201)
            ->assertJsonPath('author.name', 'New Author')
            ->assertJsonPath('author.email', 'new@example.com');

        $this->assertDatabaseHas('authors', ['email' => 'new@example.com']);
    }

    public function test_admin_created_author_can_login(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/authors', [
                'name' => 'New Author',
                'email' => 'new@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertStatus(201);

        $this->postJson('/api/v1/author/login', [
            'email' => 'new@example.com',
            'password' => 'secret123',
        ])->assertOk();
    }

    public function test_author_email_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Author::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/authors', [
                'name' => 'Duplicate',
                'email' => 'taken@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertStatus(422);
    }

    public function test_author_email_is_not_unique_across_legacy_null_emails(): void
    {
        $admin = User::factory()->admin()->create();
        Author::create(['name' => 'Legacy Author']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/authors', [
                'name' => 'Fresh Author',
                'email' => 'fresh@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertStatus(201);
    }

    public function test_author_password_is_required_for_admin_creation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/authors', [
                'name' => 'No Password',
                'email' => 'nopass@example.com',
            ])
            ->assertStatus(422);
    }

    public function test_admin_can_update_author(): void
    {
        $admin = User::factory()->admin()->create();
        $author = Author::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/authors/{$author->id}", [
                'name' => 'Renamed Author',
                'organization' => 'Updated Org',
            ])
            ->assertOk()
            ->assertJsonPath('author.name', 'Renamed Author')
            ->assertJsonPath('author.organization', 'Updated Org');
    }

    public function test_admin_can_suspend_and_activate_author(): void
    {
        $admin = User::factory()->admin()->create();
        $author = Author::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/authors/{$author->id}/status", ['status' => 'suspended'])
            ->assertOk()
            ->assertJsonPath('author.status', 'suspended');

        $this->assertTrue($author->fresh()->isSuspended());
    }

    public function test_suspending_author_revokes_tokens(): void
    {
        $admin = User::factory()->admin()->create();
        $author = Author::factory()->create();
        $token = $author->createToken('author-token')->plainTextToken;

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/authors/{$author->id}/status", ['status' => 'suspended'])
            ->assertOk();

        // Reset the cached Sanctum guard so the revoked token is actually
        // validated against the database instead of the stale admin session.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/author/me')
            ->assertStatus(401);
    }

    public function test_admin_can_reset_author_password(): void
    {
        $admin = User::factory()->admin()->create();
        $author = Author::factory()->create(['password' => 'old-password']);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/authors/{$author->id}/reset-password", [
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertOk();

        $this->postJson('/api/v1/author/login', [
            'email' => $author->email,
            'password' => 'new-password',
        ])->assertOk();

        $this->postJson('/api/v1/author/login', [
            'email' => $author->email,
            'password' => 'old-password',
        ])->assertStatus(401);
    }

    public function test_admin_can_verify_author_email(): void
    {
        $admin = User::factory()->admin()->create();
        $author = Author::factory()->unverified()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/authors/{$author->id}/verify-email")
            ->assertOk();

        $this->assertNotNull($author->fresh()->email_verified_at);
    }

    public function test_admin_can_list_authors_with_email_and_status(): void
    {
        $admin = User::factory()->admin()->create();
        Author::factory()->create(['name' => 'Searchable Author', 'email' => 'see@example.com']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/authors?search=see@example.com')
            ->assertOk()
            ->assertJsonCount(1, 'authors.data');
    }

    public function test_admin_can_view_author_books_and_sales(): void
    {
        $admin = User::factory()->admin()->create();
        $author = Author::factory()->create();
        $book = Book::factory()->create([
            'author_id' => $author->id,
            'author' => $author->name,
            'view_count' => 27,
        ]);
        Purchase::create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $book->id,
            'amount' => 9.99,
            'status' => Purchase::STATUS_PAID,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/authors/{$author->id}")
            ->assertOk()
            ->assertJsonPath('author.email', $author->email)
            ->assertJsonCount(1, 'books')
            ->assertJsonPath('books.0.view_count', 27)
            ->assertJsonPath('summary.sold', 1);
    }

    public function test_admin_list_does_not_expose_passwords(): void
    {
        $admin = User::factory()->admin()->create();
        Author::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/authors')
            ->assertOk();

        foreach ($response->json('authors.data') as $author) {
            $this->assertArrayNotHasKey('password', $author);
        }
    }

    // --- Legacy author-string migration ---

    public function test_legacy_books_are_backfilled_with_author_relationship(): void
    {
        Author::factory()->create(['name' => 'Legacy Name']);

        // Simulate a legacy book that only has the author string, no author_id.
        $book = Book::factory()->create(['author' => 'Legacy Name', 'author_id' => null]);

        // The ownedBooksQuery must still surface string-matched books.
        $author = Author::where('name', 'Legacy Name')->firstOrFail();

        $this->actingAs($author->fresh(), 'sanctum')
            ->getJson('/api/v1/author/books/'.$book->id)
            ->assertOk();
    }
}
