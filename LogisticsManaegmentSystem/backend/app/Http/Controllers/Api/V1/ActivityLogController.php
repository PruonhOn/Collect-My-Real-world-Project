<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The activity log: an append-only record of what an authenticated actor did.
 *
 * Read-only. Entries are written by the system, never created, updated or
 * deleted through this screen.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::query()->orderByDesc('created_at');

        $action = $request->query('action');
        if ($action !== null && $action !== '') {
            $query->where('action', $action);
        }

        $causerType = $request->query('causer_type');
        if ($causerType !== null && $causerType !== '') {
            $query->where('causer_type', $causerType);
        }

        $logs = $query->paginate(
            max(1, min((int) $request->query('per_page', 25), 100)),
        );

        return ApiResponse::paginated(
            $logs,
            'Activity log retrieved.',
            ActivityLogResource::collection($logs->getCollection())->resolve(),
        );
    }

    public function show(Request $request, int $log): JsonResponse
    {
        $record = ActivityLog::query()->findOrFail($log);

        return ApiResponse::success(new ActivityLogResource($record)->resolve(), 'Activity log entry retrieved.');
    }
}
