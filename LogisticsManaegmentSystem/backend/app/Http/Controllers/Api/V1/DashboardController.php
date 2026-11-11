<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\DashboardMetrics;
use Illuminate\Http\JsonResponse;

/**
 * The overview screen.
 *
 * One endpoint for the whole front page rather than one per card. The reasons are
 * practical: a dashboard that issued fourteen requests would render its cards at
 * fourteen different moments, so a count and the list it summarises could
 * disagree for as long as the screen was open; and every card here is a count of
 * the same handful of indexed columns, so a single round trip is also the cheaper
 * one.
 *
 * Read-only, and deliberately not a report: nothing here is exportable, nothing is
 * paginated, and there is no filter. What a shift needs at the start of the day is
 * the state of the operation, not a query over it.
 */
class DashboardController extends Controller
{
    public function __invoke(DashboardMetrics $metrics): JsonResponse
    {
        return ApiResponse::success(
            $metrics->summary(),
            'Dashboard summary.',
        );
    }
}
