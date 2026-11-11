<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\BusinessSubscription;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\BusinessInvitation;
use App\Services\Tenancy\BusinessContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class BusinessController extends Controller
{
    public function __construct(protected BusinessContextService $context) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $businesses = Business::whereIn('id', function ($query) use ($user) {
            $query->select('business_id')
                ->from('business_memberships')
                ->where('user_id', $user->id)
                ->where('status', 'active');
        })->orWhere('owner_id', $user->id)
            ->get()
            ->map(function ($business) use ($user) {
                $membership = $business->memberships()->where('user_id', $user->id)->first();
                return [
                    'id' => $business->id,
                    'name' => $business->name,
                    'slug' => $business->slug,
                    'currency' => $business->currency,
                    'role' => $business->owner_id === $user->id ? 'owner' : ($membership?->role ?? 'staff'),
                    'subscription_status' => $business->subscription?->status,
                    'plan' => $business->subscription?->plan?->slug,
                ];
            });

        return response()->json(['data' => $businesses]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $ownedCount = Business::where('owner_id', $user->id)->count();
        $maxBusinesses = $this->maxBusinessesFor($user);

        if ($ownedCount >= $maxBusinesses) {
            return response()->json([
                'message' => "You have reached the limit of {$maxBusinesses} business(es) on your plan.",
                'upgrade_required' => true,
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', 'size:3'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $business = DB::transaction(function () use ($validated, $user) {
            $business = Business::create([
                'owner_id' => $user->id,
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']).'-'.Str::random(6),
                'business_type' => $validated['business_type'] ?? null,
                'country' => $validated['country'] ?? null,
                'currency' => $validated['currency'] ?? 'USD',
                'timezone' => $validated['timezone'] ?? config('app.timezone', 'UTC'),
                'address' => $validated['address'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'website' => $validated['website'] ?? null,
                'is_active' => true,
            ]);

            BusinessMembership::create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'status' => 'active',
                'accepted_at' => now(),
            ]);

            $freePlan = Plan::where('slug', 'free')->first();
            if ($freePlan) {
                BusinessSubscription::create([
                    'business_id' => $business->id,
                    'plan_id' => $freePlan->id,
                    'status' => 'active',
                    'starts_at' => now(),
                ]);
            }

            return $business;
        });

        return response()->json(['data' => $business], 201);
    }

    /**
     * Details of a single business the user is allowed to manage.
     */
    public function show(Request $request, Business $business): JsonResponse
    {
        $this->assertManager($request, $business);

        return response()->json(['data' => $business]);
    }

    /**
     * Update the profile of a business. Only the owner and business admins
     * may change it, and the owner of the workspace can never be replaced
     * through this endpoint.
     */
    public function update(Request $request, Business $business): JsonResponse
    {
        $this->assertManager($request, $business);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', 'size:3'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $business->fill($validated)->save();

        return response()->json(['data' => $business->fresh()]);
    }

    /**
     * Workspace settings (tax, invoice template, notifications).
     */
    public function settings(Request $request): JsonResponse
    {
        $business = app('current.business');

        return response()->json([
            'data' => [
                'business' => $business->only(['id', 'name', 'currency', 'timezone', 'email', 'phone', 'address', 'website']),
                'settings' => $this->settingsPayload($business),
            ],
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $business = app('current.business');
        $this->assertManager($request, $business);

        $validated = $request->validate([
            'invoice_template' => ['nullable', Rule::in(['platform', 'branded'])],
            'tax_rate' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'date_format' => ['nullable', Rule::in(['Y-m-d', 'd/m/Y', 'm/d/Y'])],
            'low_stock_alerts' => ['nullable', 'boolean'],
            'auto_invoice' => ['nullable', 'boolean'],
        ]);

        $business->settings = array_merge((array) $business->settings, array_filter(
            $validated,
            fn ($value) => $value !== null
        ));

        $business->save();

        return response()->json(['data' => $this->settingsPayload($business->fresh())]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function settingsPayload(Business $business): array
    {
        return array_merge([
            'invoice_template' => 'platform',
            'tax_rate' => 0,
            'date_format' => 'Y-m-d',
            'low_stock_alerts' => false,
            'auto_invoice' => false,
        ], (array) $business->settings);
    }

    /**
     * Only the owner and business admins may manage a business.
     */
    protected function assertManager(Request $request, Business $business): void
    {
        $user = $request->user();

        if ($business->owner_id === $user->id) {
            return;
        }

        $membership = $business->memberships()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereIn('role', ['owner', 'admin'])
            ->first();

        if (! $membership) {
            abort(403, 'You are not allowed to manage this business.');
        }
    }

    public function switch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
        ]);

        $user = $request->user();
        $business = Business::find($validated['business_id']);

        $isOwner = $business && $business->owner_id === $user->id;
        $isMember = $business && $business->memberships()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (! $isOwner && ! $isMember) {
            return response()->json(['message' => 'You are not an active member of this business.'], 403);
        }

        if ($business->is_suspended) {
            return response()->json(['message' => 'Business is suspended.'], 403);
        }

        return response()->json([
            'data' => [
                'id' => $business->id,
                'name' => $business->name,
                'role' => $isOwner ? 'owner' : $business->memberships()->where('user_id', $user->id)->value('role'),
            ],
        ]);
    }

    public function invite(Request $request, Business $business): JsonResponse
    {
        $user = $request->user();

        if ($business->owner_id !== $user->id) {
            $membership = $business->memberships()->where('user_id', $user->id)->where('status', 'active')->first();
            if (! $membership || $membership->role !== 'admin') {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['nullable', Rule::in(['admin', 'staff'])],
        ]);

        $existingUser = User::where('email', $validated['email'])->first();

        if ($existingUser) {
            $already = $business->memberships()->where('user_id', $existingUser->id)->exists();
            if ($already) {
                return response()->json(['message' => 'User already has a membership in this business.'], 422);
            }
        }

        $alreadyInvited = $business->memberships()
            ->where('email', $validated['email'])
            ->where('status', 'invited')
            ->exists();

        if ($alreadyInvited) {
            return response()->json(['message' => 'An invitation for this email already exists.'], 422);
        }

        $membership = BusinessMembership::create([
            'business_id' => $business->id,
            'user_id' => $existingUser?->id,
            'email' => $validated['email'],
            'role' => $validated['role'] ?? 'staff',
            'status' => 'invited',
            'invite_token' => Str::random(48),
            'invited_at' => now(),
            'invited_by' => $user->id,
        ]);

        // The invitation email is the only way the invited person learns the
        // token, so it is sent immediately. Delivery failures must not lose
        // the invitation itself.
        try {
            Notification::route('mail', (string) $membership->email)
                ->notify(new BusinessInvitation($membership));
        } catch (Throwable $e) {
            Log::warning('Invitation email could not be sent.', [
                'business_id' => $business->id,
                'membership_id' => $membership->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['data' => $membership], 201);
    }

    protected function maxBusinessesFor(User $user): int
    {
        // Users owning a Pro-enabled business may create additional businesses
        // based on the plan_feature limit for max_businesses.
        $proBusiness = Business::where('owner_id', $user->id)
            ->whereHas('subscription', function ($q) {
                $q->whereIn('status', ['active', 'trialing']);
            })
            ->whereHas('subscription.plan', function ($q) {
                $q->where('type', 'pro');
            })
            ->first();

        if ($proBusiness) {
            $limit = \App\Services\Features\FeatureGate::class;
            $gate = app($limit);
            $value = $gate->limit($proBusiness, 'max_businesses');
            if ($value !== null) {
                return max(1, $value);
            }
        }

        return 1;
    }
}
