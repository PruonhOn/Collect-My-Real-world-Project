<?php

namespace App\Http\Controllers\Api;

use App\Models\Plan;
use Illuminate\Http\JsonResponse;

class PlanPublicController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::with('features')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $plans]);
    }
}
