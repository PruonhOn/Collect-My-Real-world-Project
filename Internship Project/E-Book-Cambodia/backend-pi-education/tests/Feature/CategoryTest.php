<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    // --- Public category tree ---

    public function test_category_tree_returns_nested_children_with_localized_names(): void
    {
        $math = Category::factory()->create(['name' => 'Math', 'slug' => 'math-aaa']);
        CategoryTranslation::factory()->for($math)->locale('en')->create(['name' => 'Math']);
        CategoryTranslation::factory()->for($math)->locale('km')->create(['name' => 'គណិតវិទ្យា']);

        $grade = Category::factory()->childOf($math)->create([
            'name' => 'Math - Grade 12',
            'slug' => 'math-grade-12-bbb',
            'position' => 12,
        ]);
        CategoryTranslation::factory()->for($grade)->locale('en')->create(['name' => 'Grade 12']);
        CategoryTranslation::factory()->for($grade)->locale('km')->create(['name' => 'គណិតថ្នាក់ទី១២']);

        // The add_level_categories migration seeds four top-level education
        // levels, so the tree contains 5 parents (Primary, Lower Secondary,
        // Upper Secondary School, Competitive Exams and Math).
        $this->getJson('/api/v1/categories?tree=1&locale=km')
            ->assertOk()
            ->assertJsonPath('locale', 'km')
            ->assertJsonCount(5, 'categories')
            ->assertJsonPath('categories.0.name', 'Math')
            ->assertJsonPath('categories.0.display_name', 'គណិតវិទ្យា')
            ->assertJsonCount(1, 'categories.0.children')
            ->assertJsonPath('categories.0.children.0.display_name', 'គណិតថ្នាក់ទី១២');
    }

    public function test_category_tree_falls_back_to_legacy_name_when_translation_is_missing(): void
    {
        Category::factory()->create(['name' => 'Physics', 'slug' => 'physics-ccc']);

        $this->getJson('/api/v1/categories?tree=1&locale=km')
            ->assertOk()
            ->assertJsonPath('categories.0.display_name', 'Physics');
    }

    public function test_category_tree_uses_default_locale_for_unsupported_locale(): void
    {
        $category = Category::factory()->create(['name' => 'Math', 'slug' => 'math-fff']);
        CategoryTranslation::factory()->for($category)->locale('en')->create(['name' => 'English Name']);
        CategoryTranslation::factory()->for($category)->locale('km')->create(['name' => 'Khmer Name']);

        $this->getJson('/api/v1/categories?tree=1&locale=zz')
            ->assertOk()
            ->assertJsonPath('categories.0.display_name', 'English Name');
    }

    // --- Book filtering through a parent category ---

    public function test_book_list_includes_child_category_books_when_requested(): void
    {
        $math = Category::factory()->create(['name' => 'Math', 'slug' => 'math-ddd']);
        $grade = Category::factory()->childOf($math)->create(['name' => 'Math - Grade 12', 'slug' => 'grade-12-ddd']);
        $history = Category::factory()->create(['name' => 'History', 'slug' => 'history-ddd']);

        Book::factory()->create(['category_id' => $math->id, 'is_published' => true]);
        Book::factory()->create(['category_id' => $grade->id, 'is_published' => true]);
        Book::factory()->create(['category_id' => $history->id, 'is_published' => true]);

        $this->getJson("/api/v1/books?category={$math->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/v1/books?category={$math->id}&include_children=1")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_category_page_orders_books_newest_first(): void
    {
        $math = Category::factory()->create(['name' => 'Math', 'slug' => 'math-order']);

        $first = Book::factory()->create(['category_id' => $math->id, 'is_published' => true]);
        $newest = Book::factory()->create(['category_id' => $math->id, 'is_published' => true]);
        Book::factory()->create(['category_id' => $math->id, 'is_published' => false]);

        $this->getJson("/api/v1/categories/{$math->id}")
            ->assertOk()
            ->assertJsonCount(2, 'books')
            ->assertJsonPath('books.0.id', $newest->id)
            ->assertJsonPath('books.1.id', $first->id);
    }

    public function test_category_page_includes_current_users_reading_progress(): void
    {
        $user = User::factory()->create();
        $math = Category::factory()->create(['name' => 'Math', 'slug' => 'math-progress']);
        $book = Book::factory()->create(['category_id' => $math->id, 'is_published' => true, 'pages' => 10]);

        ReadingProgress::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'last_page' => 4,
            'total_pages' => 10,
            'progress' => 40.0,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/categories/{$math->id}")
            ->assertOk()
            ->assertJsonPath('books.0.reading_progress.last_page', 4)
            ->assertJsonPath('books.0.reading_progress.percent', 40);
    }

    // --- Admin management ---

    public function test_admin_can_create_category_with_translations_and_parent(): void
    {
        $admin = User::factory()->admin()->create();
        $parent = Category::factory()->create(['name' => 'Math', 'slug' => 'math-eee']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/categories', [
                'name' => 'Math - Grade 12',
                'parent_id' => $parent->id,
                'position' => 12,
                'translations' => [
                    ['locale' => 'en', 'name' => 'Grade 12'],
                    ['locale' => 'km', 'name' => 'គណិតថ្នាក់ទី១២'],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('category.display_name', 'Grade 12')
            ->assertJsonPath('category.parent_id', $parent->id);

        $category = Category::where('name', 'Math - Grade 12')->firstOrFail();
        $this->assertSame($parent->id, $category->parent_id);
        $this->assertSame(12, $category->position);
        $this->assertDatabaseHas('category_translations', [
            'category_id' => $category->id,
            'locale' => 'km',
            'name' => 'គណិតថ្នាក់ទី១២',
        ]);
    }

    public function test_admin_category_creation_rejects_unknown_parent(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/categories', [
                'name' => 'Orphan',
                'parent_id' => 9999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_admin_cannot_delete_category_with_children(): void
    {
        $admin = User::factory()->admin()->create();
        $parent = Category::factory()->create(['name' => 'Math', 'slug' => 'math-ggg']);
        Category::factory()->childOf($parent)->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/categories/{$parent->id}")
            ->assertUnprocessable();

        $this->assertModelExists($parent);
    }
}
