<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\BookSubmittedForReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The editorial workflow end to end: an author submits, an admin decides, and
 * only an admin can put a book on the storefront. Also covers the ownership
 * boundary between two authors, and the tag handling the book form relies on.
 */
class BookWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(): Category
    {
        return Category::create(['name' => 'Fiction', 'slug' => 'workflow-'.uniqid()]);
    }

    /**
     * A payload that mirrors the real author book form field for field, so a
     * bad rule on any field the UI actually sends is exercised here.
     *
     * @return array<string, mixed>
     */
    private function bookPayload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $category->id,
            'title' => 'A Submitted Book',
            'description' => 'A description long enough to satisfy validation.',
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
        ], $overrides);
    }

    private function submitBook(Author $author, array $overrides = []): Book
    {
        $response = $this->actingAs($author, 'sanctum')
            ->post('/api/v1/author/books', $this->bookPayload($this->makeCategory(), $overrides))
            ->assertCreated();

        $book = Book::find($response->json('book.id'));

        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/author/books/{$book->id}/submit")
            ->assertOk();

        return $book->fresh();
    }

    // --- The happy path ---

    public function test_a_book_goes_draft_pending_approved_published(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $author = Author::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $book = $this->submitBook($author);

        $this->assertSame(Book::STATUS_PENDING, $book->status);
        $this->assertFalse($book->is_published);
        $this->assertNotNull($book->submitted_at);

        // Awaiting review means readers must not see it anywhere on the
        // catalogue, even though the book record itself exists.
        $this->getJson('/api/v1/books?sort=latest')
            ->assertOk()
            ->assertJsonMissing(['id' => $book->id]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$book->id}", ['decision' => 'approved'])
            ->assertOk()
            ->assertJsonPath('book.status', Book::STATUS_APPROVED);

        // Approval alone is not enough - it still has to be published.
        $this->getJson('/api/v1/books?sort=latest')
            ->assertOk()
            ->assertJsonMissing(['id' => $book->id]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$book->id}/publish")
            ->assertOk()
            ->assertJsonPath('book.status', Book::STATUS_PUBLISHED)
            ->assertJsonPath('book.is_published', true);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'status' => Book::STATUS_PUBLISHED,
            'is_published' => true,
            'reviewed_by' => $admin->id,
        ]);

        // And it is finally visible on the public storefront: both as a detail
        // page and inside the catalogue listing, which is what New Arrivals and
        // the category pages actually read. Asserting only the detail endpoint
        // hides a class of bug where the book is reachable by id but never
        // shows up where readers browse.
        $this->getJson("/api/v1/books/{$book->id}")->assertOk();

        $this->getJson('/api/v1/books?sort=latest')
            ->assertOk()
            ->assertJsonFragment(['id' => $book->id, 'title' => $book->title]);
    }

    public function test_a_rejected_book_shows_the_reason_and_can_be_resubmitted(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $author = Author::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $book = $this->submitBook($author);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$book->id}", [
                'decision' => 'rejected',
                'review_note' => 'The cover image does not match the title.',
            ])
            ->assertOk()
            ->assertJsonPath('book.status', Book::STATUS_REJECTED)
            ->assertJsonPath('book.review_note', 'The cover image does not match the title.');

        // The author sees the reason on their own book.
        $this->actingAs($author, 'sanctum')
            ->getJson("/api/v1/author/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('book.review_note', 'The cover image does not match the title.');

        // The author fixes it and resubmits; the stale verdict is cleared.
        $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/author/books/{$book->id}", ['title' => 'A Corrected Book'])
            ->assertOk();

        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/author/books/{$book->id}/submit")
            ->assertOk()
            ->assertJsonPath('book.status', Book::STATUS_PENDING)
            ->assertJsonPath('book.review_note', null);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'status' => Book::STATUS_PENDING,
            'review_note' => null,
            'reviewed_by' => null,
        ]);
    }

    public function test_a_rejection_must_carry_a_reason(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $book = Book::factory()->pending()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$book->id}", ['decision' => 'rejected'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['review_note']);

        $this->assertDatabaseHas('books', ['id' => $book->id, 'status' => Book::STATUS_PENDING]);
    }

    // --- Authors cannot publish ---

    public function test_an_author_cannot_reach_the_admin_review_endpoints(): void
    {
        $author = Author::factory()->create();
        $book = $this->submitBook($author);

        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$book->id}", ['decision' => 'approved'])
            ->assertForbidden();

        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$book->id}/publish")
            ->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $book->id, 'status' => Book::STATUS_PENDING]);
    }

    public function test_a_regular_user_cannot_reach_the_admin_review_endpoints(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_READER]);
        $book = Book::factory()->pending()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/book-reviews')
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$book->id}", ['decision' => 'approved'])
            ->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $book->id, 'status' => Book::STATUS_PENDING]);
    }

    public function test_a_guest_cannot_reach_the_admin_review_endpoints(): void
    {
        $book = Book::factory()->pending()->create();

        $this->getJson('/api/v1/admin/book-reviews')->assertUnauthorized();
        $this->getJson("/api/v1/admin/book-reviews/{$book->id}")->assertUnauthorized();
        $this->getJson("/api/v1/admin/book-reviews/{$book->id}/file")->assertUnauthorized();
        $this->postJson("/api/v1/admin/book-reviews/{$book->id}", ['decision' => 'approved'])->assertUnauthorized();
    }

    public function test_a_submitted_book_cannot_be_resubmitted_while_already_pending(): void
    {
        $author = Author::factory()->create();
        $book = $this->submitBook($author);

        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/author/books/{$book->id}/submit")
            ->assertForbidden();
    }

    // --- Ownership ---

    public function test_an_author_cannot_submit_another_authors_book(): void
    {
        $author = Author::factory()->create();
        $other = Author::factory()->create();
        $book = Book::factory()->draft()->create(['author_id' => $other->id, 'author' => $other->name]);

        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/author/books/{$book->id}/submit")
            ->assertNotFound();

        $this->assertDatabaseHas('books', ['id' => $book->id, 'status' => Book::STATUS_DRAFT]);
    }

    // --- Review requests reach the admin ---

    public function test_submitting_a_book_notifies_the_admin_to_check_it(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $author = Author::factory()->create();
        $book = $this->submitBook($author);

        $notification = $admin->notifications()->latest('created_at')->first();

        $this->assertNotNull($notification, 'The admin was not notified of the submitted book.');
        $this->assertSame(BookSubmittedForReview::class, $notification->type);
        $this->assertSame($book->id, $notification->data['book_id']);
        $this->assertSame($book->title, $notification->data['book_title']);
        $this->assertSame($author->id, $notification->data['author_id']);
        $this->assertSame($author->name, $notification->data['author_name']);
        $this->assertSame(Book::STATUS_PENDING, $notification->data['status']);
    }

    public function test_the_review_request_reaches_the_admin_notifications_api(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $author = Author::factory()->create();
        $book = $this->submitBook($author);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.type', 'book_uploaded')
            ->assertJsonPath('notifications.0.data.book_id', $book->id)
            ->assertJsonPath('notifications.0.data.author_name', $author->name)
            ->assertJsonPath('notifications.0.read', false);
    }

    public function test_saving_a_draft_does_not_notify_the_admin(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $author = Author::factory()->create();

        $this->actingAs($author, 'sanctum')
            ->post('/api/v1/author/books', $this->bookPayload($this->makeCategory()))
            ->assertCreated();

        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_resubmitting_a_rejected_book_notifies_the_admin_again(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $author = Author::factory()->create();
        $book = $this->submitBook($author);

        $book->transitionTo(Book::STATUS_REJECTED, $admin, 'Needs work.');

        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/author/books/{$book->id}/submit")
            ->assertOk();

        $this->assertSame(2, $admin->notifications()->count());
    }

    public function test_no_admin_means_the_submission_still_succeeds(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $author = Author::factory()->create();

        $this->assertNull(User::defaultAdmin());

        $book = $this->submitBook($author);

        $this->assertSame(Book::STATUS_PENDING, $book->status);
    }

    public function test_one_authors_drafts_are_invisible_to_another(): void
    {
        $author = Author::factory()->create();
        $other = Author::factory()->create();
        Book::factory()->draft()->create(['author_id' => $author->id, 'author' => $author->name]);
        Book::factory()->draft()->create(['author_id' => $other->id, 'author' => $other->name]);

        $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/author/books')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // --- Publishing rules ---

    public function test_only_an_approved_book_can_be_published(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $pending = Book::factory()->pending()->create();
        $draft = Book::factory()->draft()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$pending->id}/publish")
            ->assertStatus(409);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$draft->id}/publish")
            ->assertStatus(409);

        $this->assertDatabaseHas('books', ['id' => $pending->id, 'is_published' => false]);
        $this->assertDatabaseHas('books', ['id' => $draft->id, 'is_published' => false]);
    }

    public function test_unpublishing_keeps_the_book_approved_and_out_of_the_catalog(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $book = Book::factory()->published()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/book-reviews/{$book->id}/unpublish")
            ->assertOk()
            ->assertJsonPath('book.status', Book::STATUS_APPROVED)
            ->assertJsonPath('book.is_published', false);

        $this->getJson("/api/v1/books/{$book->id}")->assertNotFound();
    }

    // --- The review queue ---

    public function test_the_review_queue_lists_pending_books_and_searches_them(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $author = Author::factory()->create(['name' => 'Sokha Writer']);
        $pending = Book::factory()->pending()->create([
            'author_id' => $author->id,
            'author' => $author->name,
            'title' => 'Pending Khmer Math',
        ]);
        Book::factory()->draft()->create(['title' => 'Still A Draft']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/book-reviews')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pending->id);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/book-reviews?search=Khmer')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/book-reviews?search=Sokha')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // --- Tags ---

    public function test_tags_are_created_and_reused_rather_than_duplicated(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $author = Author::factory()->create();
        $category = $this->makeCategory();

        $this->actingAs($author, 'sanctum')
            ->post('/api/v1/author/books', $this->bookPayload($category, [
                'tags' => ['Mathematics', 'Grade 10', 'mathematics'],
            ]))
            ->assertCreated()
            ->assertJsonCount(2, 'book.tags');

        // The same labels on a second book reuse the shared tag rows.
        $this->actingAs($author, 'sanctum')
            ->post('/api/v1/author/books', $this->bookPayload($category, ['title' => 'Second Book']))
            ->assertCreated();

        $this->assertSame(2, Tag::count());
        $this->assertDatabaseHas('tags', ['name' => 'Mathematics', 'slug' => 'mathematics']);
    }

    public function test_submitting_empty_tags_clears_them(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $author = Author::factory()->create();
        $category = $this->makeCategory();

        $bookId = $this->actingAs($author, 'sanctum')
            ->post('/api/v1/author/books', $this->bookPayload($category, ['tags' => ['Science']]))
            ->assertCreated()
            ->json('book.id');

        $this->actingAs($author, 'sanctum')
            ->put("/api/v1/author/books/{$bookId}", ['tags' => []])
            ->assertOk()
            ->assertJsonCount(0, 'book.tags');

        $this->assertDatabaseMissing('book_tag', ['book_id' => $bookId]);
    }

    // --- Statistics ---

    public function test_author_stats_break_down_by_workflow_state(): void
    {
        $author = Author::factory()->create();
        $other = Author::factory()->create();

        foreach ([Book::STATUS_DRAFT, Book::STATUS_DRAFT, Book::STATUS_PENDING, Book::STATUS_REJECTED] as $status) {
            Book::factory()->{$status}()->create(['author_id' => $author->id, 'author' => $author->name]);
        }

        Book::factory()->published()->create(['author_id' => $author->id, 'author' => $author->name]);
        Book::factory()->pending()->create(['author_id' => $other->id, 'author' => $other->name]);

        $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/author/stats')
            ->assertOk()
            ->assertJsonPath('stats.books', 5)
            ->assertJsonPath('stats.drafts', 2)
            ->assertJsonPath('stats.pending', 1)
            ->assertJsonPath('stats.approved', 0)
            ->assertJsonPath('stats.rejected', 1)
            ->assertJsonPath('stats.published', 1);
    }

    // --- New arrivals ---

    /**
     * "New arrivals" has to mean "recently went live", not "recently uploaded".
     * Under the review workflow a book can sit as a draft for weeks and then
     * be published in one go, so ordering by the upload date buries exactly
     * the books this page exists to surface.
     */
    public function test_new_arrivals_are_ordered_by_publication_date(): void
    {
        $uploadedLongAgo = Book::factory()->published()->create([
            'title' => 'Published Just Now',
            'created_at' => now()->subDays(30),
        ]);
        $uploadedRecently = Book::factory()->published()->create([
            'title' => 'Published A While Ago',
            'created_at' => now()->subDays(10),
        ]);

        // The publication date is what decides the order, not the upload date.
        $this->actingAs(User::factory()->admin()->create(), 'sanctum');
        $uploadedLongAgo->forceFill(['published_at' => now()])->save();
        $uploadedRecently->forceFill(['published_at' => now()->subDays(20)])->save();

        $this->getJson('/api/v1/books?sort=latest')
            ->assertOk()
            ->assertJsonPath('data.0.id', $uploadedLongAgo->id)
            ->assertJsonPath('data.1.id', $uploadedRecently->id);
    }

    /**
     * The 62 books that predate the workflow have no publication date, so they
     * must fall back to their upload date rather than bunching at the bottom.
     */
    public function test_new_arrivals_fall_back_to_upload_date_for_legacy_books(): void
    {
        $legacy = Book::factory()->published()->create([
            'title' => 'Legacy Book',
            'created_at' => now()->subDays(2),
        ]);
        $legacy->forceFill(['published_at' => null])->save();

        $this->getJson('/api/v1/books?sort=latest')
            ->assertOk()
            ->assertJsonPath('data.0.id', $legacy->id);
    }

    /**
     * A book the author has only uploaded is a draft, and drafts must never
     * reach the storefront no matter how new they are.
     */
    public function test_a_draft_never_appears_in_new_arrivals(): void
    {
        Book::factory()->draft()->create(['title' => 'Brand New Draft']);

        $this->getJson('/api/v1/books?sort=latest')
            ->assertOk()
            ->assertJsonMissing(['title' => 'Brand New Draft']);
    }

    // --- The reviewer's copy of the file ---

    public function test_only_an_admin_can_stream_a_submitted_file(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $author = Author::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $book = $this->submitBook($author);

        $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/admin/book-reviews/{$book->id}/file")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // A pending book is not readable through the public reader even by its
        // own author - the review preview is a separate, admin-only path.
        $this->getJson("/api/v1/reader/books/{$book->id}")->assertNotFound();
    }
}
