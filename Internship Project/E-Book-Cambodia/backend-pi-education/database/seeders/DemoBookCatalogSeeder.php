<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use Database\Seeders\Catalog\DemoBook;
use Database\Seeders\Catalog\DemoCatalog;
use Database\Seeders\Catalog\DemoContentBuilder;
use Database\Seeders\Catalog\DemoCoverFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Publishes the demo catalogue.
 *
 * The seeder is idempotent: running it twice leaves the same rows, the same
 * covers and the same published dates. It only ever writes rows whose slug
 * starts with "demo-", and it refuses to touch a row that already belongs to
 * a real author, so it can be run against a live database safely.
 */
class DemoBookCatalogSeeder extends Seeder
{
    /**
     * Categories the demo catalogue uses, resolved once per run.
     *
     * @var array<string, Category>
     */
    private array $categories = [];

    public function run(): void
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach (DemoCatalog::books() as $record) {
            $book = Book::query()->where('slug', $record['slug'])->first();

            // A slug that belongs to a real book is never overwritten.
            if ($book !== null && $book->author_id !== null) {
                $skipped++;

                continue;
            }

            $isNew = $book === null;
            $book ??= new Book(['slug' => $record['slug']]);

            $book->fill($this->attributes($record));

            if ($isNew) {
                $book->published_at = now()->subDays((int) $record['days_ago']);
            }

            $book->save();

            if ($isNew) {
                $created++;
            } else {
                $updated++;
            }
        }

        $this->command?->info(sprintf(
            'Demo catalogue ready: %d created, %d updated, %d skipped.',
            $created,
            $updated,
            $skipped
        ));
    }

    /**
     * The book columns a catalogue record maps onto.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function attributes(array $record): array
    {
        $isVip = (bool) $record['vip'];
        $isFree = ! $isVip && (bool) $record['free'];
        $price = ($isFree || $isVip) ? 0.0 : (float) $record['price'];

        return [
            'category_id' => $this->resolveCategory((string) $record['category'])->id,
            'title' => $record['title'],
            'slug' => $record['slug'],
            'description' => $record['description'],
            'content' => DemoContentBuilder::build($record),
            'author' => $record['author'],
            'publisher' => null,
            'subject' => $record['subject'],
            'education_level' => $record['level'],
            'grade' => $record['grade'],
            'audience' => $record['audience'],
            'language' => $record['language'],
            'publication_date' => null,
            'cover_image' => $this->cover($record),
            'cover_disk' => config('books.cover_disk'),
            'file_path' => null,
            'file_disk' => null,
            'file_name' => null,
            'file_type' => 'text',
            'file_size' => 0,
            'pages' => DemoContentBuilder::pageCount($record),
            'price' => $price,
            'preview_pages' => $record['preview_pages'],
            'preview_mode' => $record['preview_mode'],
            'is_free' => $isFree,
            'is_published' => true,
            'is_vip_only' => $isVip,
            'is_coming_soon' => (bool) $record['soon'],
            'view_count' => (int) $record['views'],
            'purchase_count' => (int) $record['purchases'],
            'status' => Book::STATUS_PUBLISHED,
        ];
    }

    /**
     * The placeholder cover of a record, generated once and then reused.
     *
     * @param  array<string, mixed>  $record
     */
    private function cover(array $record): ?string
    {
        $label = $record['grade'] ?: $record['level'];

        return DemoCoverFactory::ensure(
            (string) $record['slug'],
            (string) $record['subject'],
            is_string($label) ? $label : ''
        );
    }

    /**
     * Find the category a record belongs to, creating it when it is missing.
     *
     * The categories of the demo catalogue already exist in a seeded
     * database; the fallback keeps the seeder working on a database that only
     * holds the migrations.
     */
    private function resolveCategory(string $name): Category
    {
        if (isset($this->categories[$name])) {
            return $this->categories[$name];
        }

        $category = Category::query()->where('name', $name)->first();

        if ($category === null) {
            $category = Category::create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'description' => $this->describe($name),
            ]);
        }

        $this->attachParent($category);

        return $this->categories[$name] = $category;
    }

    /**
     * Give a grade shelf its subject as parent, the way the seeded taxonomy
     * does it.
     */
    private function attachParent(Category $category): void
    {
        if ($category->parent_id !== null) {
            return;
        }

        $parentName = match (true) {
            str_starts_with($category->name, 'Math - ') => DemoBook::CATEGORY_MATH,
            str_starts_with($category->name, 'Khmer - ') => DemoBook::CATEGORY_KHMER,
            default => null,
        };

        if ($parentName === null) {
            return;
        }

        $parent = Category::query()->where('name', $parentName)->first();

        if ($parent === null || $parent->id === $category->id) {
            return;
        }

        $category->parent_id = $parent->id;
        $category->save();
    }

    /**
     * A slug that is unique even when the name has no Latin characters.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'category';
        }

        do {
            $slug = $base.'-'.Str::lower(Str::random(4));
        } while (Category::query()->where('slug', $slug)->exists());

        return $slug;
    }

    /**
     * A short description for a category the seeder had to create.
     */
    private function describe(string $name): string
    {
        return $name.' — demo catalogue shelf.';
    }
}
