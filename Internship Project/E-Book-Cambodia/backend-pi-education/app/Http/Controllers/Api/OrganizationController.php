<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrganizationDepartment;
use App\Models\OrganizationMember;
use Illuminate\Http\JsonResponse;

class OrganizationController extends Controller
{
    public function index(): JsonResponse
    {
        $departments = OrganizationDepartment::with(['members' => function ($q) {
            $q->where('is_active', true)
                ->with(['position', 'department', 'reportsTo', 'responsibilities'])
                ->orderBy('sort_order')
                ->orderBy('id');
        }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $members = OrganizationMember::with(['position', 'department', 'reportsTo', 'responsibilities', 'partner'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'departments' => $departments,
            'members' => $members,
        ]);
    }

    public function show(OrganizationMember $member): JsonResponse
    {
        $member->load(['position', 'department', 'reportsTo', 'subordinates', 'responsibilities', 'partner']);
        if (! $member->is_active) {
            abort(404);
        }

        return response()->json(['member' => $member]);
    }
}
