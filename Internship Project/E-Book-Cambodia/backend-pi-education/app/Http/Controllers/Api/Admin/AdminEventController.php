<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminEventController extends Controller
{
    private const COVER_DIR = 'event-covers';

    private const IMAGE_RULES = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'];

    public function index(Request $request): JsonResponse
    {
        $events = Event::query()
            ->withCount('registrations')
            ->when($request->input('search'), function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('organizer', 'like', "%{$search}%");
                });
            })
            ->when($request->input('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->boolean('featured'), fn ($query) => $query->where('is_featured', true))
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 20))
            ->withQueryString();

        return response()->json(['events' => $events]);
    }

    public function show(Event $event): JsonResponse
    {
        $event->loadCount('registrations');

        return response()->json(['event' => $event]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedData($request, forUpdate: false);

        $validated = $this->normalizeSocialLinks($validated);

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store(self::COVER_DIR, 'public');
        }

        $event = Event::create($validated);

        return response()->json(['event' => $event->loadCount('registrations')], 201);
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $validated = $this->validatedData($request, forUpdate: true);

        $validated = $this->normalizeSocialLinks($validated);

        if ($request->hasFile('cover_image')) {
            $this->deleteCover($event);
            $validated['cover_image'] = $request->file('cover_image')->store(self::COVER_DIR, 'public');
        } elseif ($request->boolean('remove_cover_image')) {
            $this->deleteCover($event);
            $validated['cover_image'] = null;
        }

        $event->update($validated);

        return response()->json(['event' => $event->fresh()->loadCount('registrations')]);
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->deleteCover($event);
        $event->delete();

        return response()->json(['message' => 'Event deleted.']);
    }

    /**
     * Admin view of registered participants.
     */
    public function registrations(Request $request, Event $event): JsonResponse
    {
        $registrations = $event->registrations()
            ->with('user:id,name,email,avatar')
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 20))
            ->withQueryString();

        return response()->json(['registrations' => $registrations]);
    }

    public function destroyRegistration(Event $event, EventRegistration $registration): JsonResponse
    {
        $registration->delete();

        return response()->json(['message' => 'Registration cancelled.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, bool $forUpdate): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'cover_image' => self::IMAGE_RULES,
            'category' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'location' => ['required', 'string', 'max:255'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'registration_link' => ['nullable', 'url', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'fee_type' => ['required', 'in:free,paid'],
            'fee_amount' => ['nullable', 'numeric', 'min:0', 'required_if:fee_type,paid'],
            'speaker' => ['nullable', 'string', 'max:255'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'url', 'max:2048'],
            'status' => ['required', 'in:draft,published,cancelled,completed'],
            'is_featured' => ['sometimes', 'boolean'],
            'remove_cover_image' => ['sometimes', 'boolean'],
        ];

        // Updates may be partial (e.g. a status or featured toggle), so the
        // required fields only apply when the field is actually present.
        if ($forUpdate) {
            foreach ($rules as $field => $fieldRules) {
                $rules[$field] = ['sometimes', ...$fieldRules];
            }
        }

        return $request->validate($rules);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeSocialLinks(array $data): array
    {
        if (array_key_exists('social_links', $data)) {
            $links = array_values(array_filter($data['social_links'], fn ($link) => is_string($link) && $link !== ''));
            $data['social_links'] = $links === [] ? null : $links;
        }

        return $data;
    }

    private function deleteCover(Event $event): void
    {
        if ($event->cover_image && ! str_starts_with($event->cover_image, 'http')) {
            Storage::disk('public')->delete($event->cover_image);
        }
    }
}
