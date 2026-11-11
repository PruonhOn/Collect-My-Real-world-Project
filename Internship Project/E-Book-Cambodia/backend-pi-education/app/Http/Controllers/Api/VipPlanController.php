<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VipPlan;
use App\Services\VipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VipPlanController extends Controller
{
    /**
     * Public listing of active VIP plans. Test cards are hidden unless the
     * app runs in test mode.
     */
    public function index(): JsonResponse
    {
        $plans = VipPlan::where('is_active', true)
            ->when(! VipService::testModeEnabled(), fn ($query) => $query->where('is_test', false))
            ->orderBy('price')
            ->get();

        return response()->json(['plans' => $plans]);
    }

    /**
     * Admin listing: every plan, including inactive and test cards, so an
     * admin can review and re-price them in one place. Only admins reach this
     * endpoint, so the test cards the public listing hides are safe to show.
     */
    public function adminIndex(): JsonResponse
    {
        $plans = VipPlan::orderBy('is_test')
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
            'price' => ['required', 'numeric', 'min:0.01'],
            'is_test' => ['nullable', 'boolean'],
            'duration_hours' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated = $this->normalizeTerm($validated);

        $plan = VipPlan::create($validated);

        return response()->json(['plan' => $plan], 201);
    }

    /**
     * Price-only updates leave the plan's term alone: normalizing it would
     * silently convert an hourly test card into a one-year production plan.
     */
    public function update(Request $request, VipPlan $vipPlan): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:vip_plans,slug,'.$vipPlan->id],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0.01'],
            'is_test' => ['nullable', 'boolean'],
            'duration_hours' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['is_test']) || ($validated['duration_hours'] ?? null) !== null) {
            $validated = $this->normalizeTerm($validated);
        }

        $vipPlan->update($validated);

        return response()->json(['plan' => $vipPlan->fresh()]);
    }

    public function destroy(VipPlan $vipPlan): JsonResponse
    {
        $vipPlan->delete();

        return response()->json(['message' => 'VIP plan deleted.']);
    }

    /**
     * Split plan terms into the fixed one-year production term or the
     * configurable hour-based test-card term.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeTerm(array $validated): array
    {
        if (filter_var($validated['is_test'] ?? false, FILTER_VALIDATE_BOOL)) {
            abort_unless(VipService::testModeEnabled(), 403, 'Test VIP plans can only be created when VIP_TEST_MODE is enabled.');

            $validated['is_test'] = true;
            $validated['duration_hours'] = (int) ($validated['duration_hours'] ?? VipService::TEST_SUBSCRIPTION_HOURS);
            $validated['duration_days'] = 0;
        } else {
            $validated['is_test'] = false;
            $validated['duration_hours'] = null;
            $validated['duration_days'] = VipService::SUBSCRIPTION_DAYS;
        }

        return $validated;
    }
}
