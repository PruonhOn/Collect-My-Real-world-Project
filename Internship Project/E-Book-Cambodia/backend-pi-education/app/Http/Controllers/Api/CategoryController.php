<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ReadingProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct(private ReadingProgressService $readingProgress) {}

    public function index(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $hasBooks = $request->input('has_books') === '1';

        if ($request->boolean('tree')) {
            $categories = Category::query()
                ->whereNull('parent_id')
                ->withCount('books')
                ->when($hasBooks, fn ($query) => $query->has('books'))
                ->with([
                    'translations',
                    'children' => fn ($query) => $query
                        ->withCount('books')
                        ->when($hasBooks, fn ($child) => $child->has('books'))
                        ->with('translations'),
                ])
                ->orderBy('position')
                ->orderBy('name')
                ->get();

            return response()->json([
                'locale' => $locale,
                'categories' => $categories
                    ->map(fn (Category $category) => $this->formatCategory($category, $locale, withChildren: true))
                    ->values(),
            ]);
        }

        $categories = Category::withCount('books')
            ->with('translations')
            ->when($hasBooks, fn ($query) => $query->has('books'))
            ->orderBy('name')
            ->get();

        return response()->json([
            'locale' => $locale,
            'categories' => $categories
                ->map(fn (Category $category) => $this->formatCategory($category, $locale))
                ->values(),
        ]);
    }

    public function show(Request $request, Category $category): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $category->loadCount('books')->load([
            'translations',
            'children' => fn ($query) => $query->withCount('books')->with('translations'),
        ]);

        $books = $category->books()->published()->latest('id')->get();

        $this->readingProgress->attachTo($books, auth('sanctum')->id());

        return response()->json([
            'category' => $this->formatCategory($category, $locale, withChildren: true),
            'books' => $books,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateCategory($request);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(4)),
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'position' => $validated['position'] ?? 0,
        ]);

        $this->syncTranslations($category, $validated['translations'] ?? []);
        $category->loadCount('books')->load('translations');

        return response()->json([
            'category' => $this->formatCategory($category, $this->resolveLocale($request), withChildren: true),
        ], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $this->validateCategory($request, $category);

        if (isset($validated['name'])) {
            $category->name = $validated['name'];
            $category->slug = Str::slug($validated['name']).'-'.Str::lower(Str::random(4));
        }
        if (array_key_exists('description', $validated)) {
            $category->description = $validated['description'];
        }
        if (array_key_exists('parent_id', $validated)) {
            $category->parent_id = $validated['parent_id'];
        }
        if (isset($validated['position'])) {
            $category->position = $validated['position'];
        }
        $category->save();

        $this->syncTranslations($category, $validated['translations'] ?? []);
        $category->loadCount('books')->load('translations');

        return response()->json([
            'category' => $this->formatCategory($category, $this->resolveLocale($request), withChildren: true),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a category that still has subcategories.',
            ], 422);
        }

        if ($category->books()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a category that still contains books.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCategory(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => [$category ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'position' => ['sometimes', 'integer', 'min:0'],
            'translations' => ['sometimes', 'array'],
            'translations.*.locale' => ['required_with:translations', 'string', 'max:8'],
            'translations.*.name' => ['required_with:translations', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
        ]);
    }

    /**
     * @param  array<int, array{locale: string, name: string, description?: string|null}>  $translations
     */
    private function syncTranslations(Category $category, array $translations): void
    {
        foreach ($translations as $translation) {
            $category->translations()->updateOrCreate(
                ['locale' => $translation['locale']],
                [
                    'name' => $translation['name'],
                    'description' => $translation['description'] ?? null,
                ]
            );
        }

        if (! $category->translations()->where('locale', 'en')->exists()) {
            $category->translations()->create([
                'locale' => 'en',
                'name' => $category->name,
                'description' => $category->description,
            ]);
        }
    }

    private function resolveLocale(Request $request): string
    {
        $locale = (string) $request->input('locale', app()->getLocale());

        return in_array($locale, Category::SUPPORTED_LOCALES, true) ? $locale : app()->getLocale();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCategory(Category $category, string $locale, bool $withChildren = false): array
    {
        $data = [
            'id' => $category->id,
            'parent_id' => $category->parent_id,
            'name' => $category->name,
            'display_name' => $category->translatedName($locale),
            'description' => $category->description,
            'display_description' => $category->translatedDescription($locale),
            'slug' => $category->slug,
            'position' => $category->position,
            'books_count' => $category->books_count ?? null,
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at,
        ];

        if ($withChildren) {
            $data['children'] = $category->children
                ->map(fn (Category $child) => $this->formatCategory($child, $locale))
                ->values()
                ->all();
        }

        return $data;
    }
}
