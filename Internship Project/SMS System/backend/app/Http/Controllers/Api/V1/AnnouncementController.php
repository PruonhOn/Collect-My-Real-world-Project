<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Announcement\StoreAnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends BaseApiController
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Announcement::query()->with('createdBy');

        if (! $request->user()->can('announcements.manage')) {
            $query->where('published_at', '<=', now())
                ->where('expires_at', '>', now())
                ->whereJsonContains('audience', $request->user()->roles->pluck('name')->first() ?? 'all');
        }

        $paginator = $this->paginate($query, $request, ['title'], [], 'published_at', 'desc');

        return response()->json([
            'data' => AnnouncementResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        $announcement = Announcement::create([
            'title' => $request->input('title'),
            'body' => $request->input('body'),
            'audience' => $request->input('audience'),
            'published_at' => $request->date('published_at') ?? now(),
            'expires_at' => $request->date('expires_at'),
            'created_by' => $request->user()->getKey(),
        ]);

        $notified = $this->notifications->announce($announcement);
        $this->audit->record('announcement.created', $announcement, after: ['notified' => $notified]);

        return response()->json([
            'data' => new AnnouncementResource($announcement),
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function show(Request $request, Announcement $announcement): JsonResponse
    {
        return response()->json([
            'data' => new AnnouncementResource($announcement->load('createdBy')),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:191'],
            'body' => ['sometimes', 'string', 'max:20000'],
            'audience' => ['sometimes', 'array'],
            'audience.*' => ['string', 'in:all,admin,teacher,staff,student,parent'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
        ]);

        $announcement->fill($validated)->save();

        if ($request->boolean('re_fanout', false)) {
            $this->notifications->announce($announcement);
        }

        $this->audit->record('announcement.updated', $announcement, after: $announcement->toArray());

        return response()->json([
            'data' => new AnnouncementResource($announcement),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $this->audit->record('announcement.deleted', $announcement, before: $announcement->toArray());
        $announcement->delete();

        return response()->json([
            'data' => ['message' => 'Announcement deleted.'],
            'meta' => null,
            'errors' => null,
        ]);
    }
}
