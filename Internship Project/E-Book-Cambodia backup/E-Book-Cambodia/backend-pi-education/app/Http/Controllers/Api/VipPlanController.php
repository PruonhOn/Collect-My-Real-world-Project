<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VipPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VipPlanController extends Controller
{
    /**
     * Public listing of active VIP plans.
     */
    public function index(): JsonResponse
    {
        $plans = VipPlan::where('is_active', true)
            ->orderBy('price')
            ->get();

        return response()->json(['plans' => $plans]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:vip_plans,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $plan = VipPlan::create($validated);

        return response()->json(['plan' => $plan], 201);
    }

    public function update(Request $request, VipPlan $vipPlan): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:vip_plans,slug,'.$vipPlan->id],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'duration_days' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $vipPlan->update($validated);

        return response()->json(['plan' => $vipPlan->fresh()]);
    }

    public function destroy(VipPlan $vipPlan): JsonResponse
    {
        $vipPlan->delete();

        return response()->json(['message' => 'VIP plan deleted.']);
    }
}
