<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Models\Plan;
use App\Services\Features\FeatureGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function __construct(protected FeatureGate $gate) {}

    public function index(): JsonResponse
    {
        $plans = Plan::with('features')->orderBy('sort_order')->get()->map(fn ($plan) => $this->present($plan));

        return response()->json(['data' => $plans]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:plans,slug'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::in(['free', 'pro'])],
            'price_monthly' => ['required', 'integer', 'min:0'],
            'price_yearly' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'trial_enabled' => ['nullable', 'boolean'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'features' => ['nullable', 'array'],
            'features.*.feature_id' => ['required', 'integer', 'exists:features,id'],
            'features.*.enabled' => ['nullable', 'boolean'],
            'features.*.limit_value' => ['nullable', 'integer', 'min:0'],
        ]);

        $plan = Plan::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'price_monthly' => $validated['price_monthly'],
            'price_yearly' => $validated['price_yearly'],
            'currency' => strtoupper($validated['currency']),
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
            'trial_enabled' => $validated['trial_enabled'] ?? true,
            'trial_days' => $validated['trial_days'] ?? 14,
        ]);

        $this->syncFeatures($plan, $validated['features'] ?? []);
        $this->gate->invalidate($plan);

        return response()->json(['data' => $this->present($plan->fresh('features'))], 201);
    }

    public function show(Plan $plan): JsonResponse
    {
        return response()->json(['data' => $this->present($plan->load('features'))]);
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_monthly' => ['sometimes', 'integer', 'min:0'],
            'price_yearly' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'trial_enabled' => ['sometimes', 'boolean'],
            'trial_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'features' => ['nullable', 'array'],
            'features.*.feature_id' => ['required', 'integer', 'exists:features,id'],
            'features.*.enabled' => ['nullable', 'boolean'],
            'features.*.limit_value' => ['nullable', 'integer', 'min:0'],
        ]);

        // Price and feature changes apply to NEW subscriptions and renewals.
        // Existing subscriptions keep the terms captured on their invoices and
        // payment records; nothing historical is rewritten.
        $plan->fill(collect($validated)->except('features')->all());
        $plan->save();

        if (array_key_exists('features', $validated)) {
            $this->syncFeatures($plan, $validated['features']);
        }

        $this->gate->invalidate($plan);

        return response()->json(['data' => $this->present($plan->fresh('features'))]);
    }

    public function destroy(Plan $plan): JsonResponse
    {
        $inUse = $plan->subscriptions()->whereIn('status', ['active', 'trialing', 'past_due'])->exists();

        if ($inUse) {
            return response()->json([
                'message' => 'This plan is attached to live subscriptions. Deactivate it instead of deleting it.',
            ], 422);
        }

        $plan->delete();

        return response()->json(['message' => 'Plan deleted.']);
    }

    protected function syncFeatures(Plan $plan, array $features): void
    {
        $sync = [];

        foreach ($features as $feature) {
            $sync[$feature['feature_id']] = [
                'enabled' => $feature['enabled'] ?? true,
                'limit_value' => $feature['limit_value'] ?? null,
            ];
        }

        $plan->features()->sync($sync);
    }

    protected function present(Plan $plan): array
    {
        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'description' => $plan->description,
            'type' => $plan->type,
            'price_monthly' => $plan->price_monthly,
            'price_yearly' => $plan->price_yearly,
            'currency' => $plan->currency,
            'is_active' => $plan->is_active,
            'sort_order' => $plan->sort_order,
            'trial_enabled' => $plan->trial_enabled,
            'trial_days' => $plan->trial_days,
            'features' => $plan->features->map(fn ($feature) => [
                'feature_id' => $feature->id,
                'key' => $feature->key,
                'name' => $feature->name,
                'type' => $feature->type,
                'enabled' => (bool) $feature->pivot->enabled,
                'limit' => $feature->pivot->limit_value === null ? null : (int) $feature->pivot->limit_value,
            ]),
        ];
    }
}
