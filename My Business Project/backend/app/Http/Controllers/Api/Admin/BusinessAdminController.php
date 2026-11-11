<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Business::query()->with(['owner', 'subscription.plan']);

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->query('status') === 'suspended') {
            $query->where('is_suspended', true);
        } elseif ($request->query('status') === 'active') {
            $query->where('is_suspended', false);
        }

        $businesses = $query->orderBy('created_at', 'desc')
            ->paginate(min(100, (int) $request->query('per_page', 20)));

        return response()->json($businesses);
    }

    public function show(Business $business): JsonResponse
    {
        return response()->json([
            'data' => $business->load(['owner', 'subscription.plan', 'memberships.user']),
        ]);
    }

    public function update(Request $request, Business $business): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', 'size:3'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $before = $business->only(array_keys($validated));
        $business->fill($validated)->save();

        $this->audit($request, $business, 'update_business', $request->input('reason'), $before, $business->only(array_keys($validated)));

        return response()->json(['data' => $business->fresh()]);
    }

    public function suspend(Request $request, Business $business): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($business->is_suspended) {
            return response()->json(['message' => 'Business is already suspended.'], 422);
        }

        $business->update([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspended_by' => $request->user()->id,
            'suspension_reason' => $validated['reason'],
        ]);

        $this->audit($request, $business, 'suspend_business', $validated['reason'], ['is_suspended' => false], ['is_suspended' => true]);

        return response()->json(['data' => $business->fresh()]);
    }

    public function unsuspend(Request $request, Business $business): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if (! $business->is_suspended) {
            return response()->json(['message' => 'Business is not suspended.'], 422);
        }

        $business->update([
            'is_suspended' => false,
            'suspended_at' => null,
            'suspended_by' => null,
            'suspension_reason' => null,
        ]);

        $this->audit($request, $business, 'unsuspend_business', $validated['reason'], ['is_suspended' => true], ['is_suspended' => false]);

        return response()->json(['data' => $business->fresh()]);
    }

    public function destroy(Request $request, Business $business): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->audit($request, $business, 'delete_business', $validated['reason']);

        $business->delete();

        return response()->json(['message' => 'Business deleted.']);
    }

    protected function audit(Request $request, Business $business, string $action, ?string $reason, ?array $old = null, ?array $new = null): void
    {
        \App\Models\AdminAuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'admin.'.$action,
            'target_type' => Business::class,
            'target_id' => $business->id,
            'reason' => $reason,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
