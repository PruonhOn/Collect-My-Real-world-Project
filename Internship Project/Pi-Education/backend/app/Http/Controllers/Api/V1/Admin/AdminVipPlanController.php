<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VipPlan\StoreVipPlanRequest;
use App\Http\Requests\Admin\VipPlan\UpdateVipPlanRequest;
use App\Http\Resources\VipPlanResource;
use App\Models\VipPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminVipPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $plans = VipPlan::query()
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return $this->success([
            'plans' => $plans->map(function (VipPlan $plan) {
                return array_merge(
                    (new VipPlanResource($plan))->resolve(),
                    ['is_active' => $plan->is_active]
                );
            }),
        ], 'VIP plans retrieved.');
    }

    public function store(StoreVipPlanRequest $request): JsonResponse
    {
        $plan = VipPlan::create($request->safe()->only([
            'slug', 'price', 'duration_days', 'features', 'is_active', 'sort_order',
        ]));

        foreach ($request->input('translations', []) as $locale => $fields) {
            $plan->translations()->updateOrCreate(['locale' => $locale], $fields);
        }

        return $this->success(
            new VipPlanResource($plan->load('translations')),
            'VIP plan created.',
            201
        );
    }

    public function update(string $id, UpdateVipPlanRequest $request): JsonResponse
    {
        $plan = VipPlan::find($id);

        if (! $plan) {
            return $this->error('VIP plan not found.', null, 404);
        }

        $plan->update($request->safe()->only([
            'slug', 'price', 'duration_days', 'features', 'is_active', 'sort_order',
        ]));

        foreach ($request->input('translations', []) as $locale => $fields) {
            $plan->translations()->updateOrCreate(['locale' => $locale], $fields);
        }

        return $this->success(
            new VipPlanResource($plan->load('translations')),
            'VIP plan updated.'
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $plan = VipPlan::find($id);

        if (! $plan) {
            return $this->error('VIP plan not found.', null, 404);
        }

        $plan->delete();

        return $this->success(null, 'VIP plan deleted.');
    }
}
