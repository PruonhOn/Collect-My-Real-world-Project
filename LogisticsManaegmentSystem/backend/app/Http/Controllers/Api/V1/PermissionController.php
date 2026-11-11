<?php

namespace App\Http\Controllers\Api\V1;

use App\Support\ApiResponse;
use App\Support\PermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The permission inventory, grouped exactly as config/permissions.php presents
 * it. Used by the Roles & Permissions screen to render one section per group
 * and one checkbox per permission.
 */
class PermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $groups = PermissionCatalog::groups();

        return ApiResponse::success($groups, 'Permissions retrieved.');
    }
}
