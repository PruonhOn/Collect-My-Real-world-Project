<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminAuthorController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $authors = Author::with(['translations'])
            ->withCount('books')
            ->when($request->has('search') && $request->search, function ($q) use ($request) {
                $q->whereHas('translations', function ($t) use ($request) {
                    $t->where('name', 'like', '%'.$request->search.'%');
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        $mapped = $authors->getCollection()->map(function ($author) {
            $km = $author->translations()->where('locale', 'km')->first();
            $en = $author->translations()->where('locale', 'en')->first();

            return [
                'id' => $author->id,
                'slug' => $author->slug,
                'name_km' => $km->name ?? null,
                'name_en' => $en->name ?? null,
                'bio_km' => $km->bio ?? null,
                'bio_en' => $en->bio ?? null,
                'photo' => $author->photo ? url('/storage/'.$author->photo) : null,
                'books_count' => $author->books_count ?? 0,
                'created_at' => $author->created_at->toDateTimeString(),
            ];
        });

        return $this->success([
            'authors' => $mapped,
            'pagination' => [
                'current_page' => $authors->currentPage(),
                'last_page' => $authors->lastPage(),
                'per_page' => $authors->perPage(),
                'total' => $authors->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name_km' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'bio_km' => ['nullable', 'string'],
            'bio_en' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $slug = Str::slug($request->name_en ?: $request->name_km);
        $originalSlug = $slug;
        $counter = 1;
        while (Author::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        $data = ['slug' => $slug];

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('authors', 'public');
        }

        $author = Author::create($data);

        $author->translations()->create([
            'locale' => 'km',
            'name' => $request->name_km,
            'bio' => $request->bio_km,
        ]);

        if ($request->name_en) {
            $author->translations()->create([
                'locale' => 'en',
                'name' => $request->name_en,
                'bio' => $request->bio_en,
            ]);
        }

        return $this->success($author->load('translations'), 'Author created successfully', 201);
    }

    public function update(Request $request, int $id)
    {
        $author = Author::find($id);

        if (! $author) {
            return $this->error('Author not found', 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name_km' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'bio_km' => ['nullable', 'string'],
            'bio_en' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = [];

        if ($request->hasFile('photo')) {
            if ($author->photo) {
                Storage::disk('public')->delete($author->photo);
            }
            $data['photo'] = $request->file('photo')->store('authors', 'public');
            $author->update($data);
        }

        if ($request->has('name_km')) {
            $km = $author->translations()->where('locale', 'km')->first();
            if ($km) {
                $km->update(['name' => $request->name_km, 'bio' => $request->bio_km]);
            } else {
                $author->translations()->create([
                    'locale' => 'km',
                    'name' => $request->name_km,
                    'bio' => $request->bio_km,
                ]);
            }
        }

        if ($request->has('name_en')) {
            $en = $author->translations()->where('locale', 'en')->first();
            if ($request->name_en) {
                if ($en) {
                    $en->update(['name' => $request->name_en, 'bio' => $request->bio_en]);
                } else {
                    $author->translations()->create([
                        'locale' => 'en',
                        'name' => $request->name_en,
                        'bio' => $request->bio_en,
                    ]);
                }
            }
        }

        return $this->success($author->fresh('translations'), 'Author updated successfully');
    }

    public function destroy(Request $request, int $id)
    {
        $author = Author::find($id);

        if (! $author) {
            return $this->error('Author not found', 404);
        }

        if ($author->books()->exists()) {
            return $this->error('Cannot delete author who has books', 422);
        }

        if ($author->photo) {
            Storage::disk('public')->delete($author->photo);
        }

        $author->delete();

        return $this->success(null, 'Author deleted successfully');
    }
}