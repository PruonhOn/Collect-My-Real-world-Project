<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Api\Controller;
use App\Models\BusinessMembership;
use App\Models\User;
use App\Notifications\BusinessInvitation;
use App\Services\Features\FeatureGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class TeamController extends Controller
{
    public function __construct(protected FeatureGate $gate) {}

    public function index(): JsonResponse
    {
        $business = app('current.business');

        $members = $business->memberships()
            ->with('user:id,name,email')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'role' => $m->role,
                'status' => $m->status,
                'invited_at' => $m->invited_at?->toIso8601String(),
                'accepted_at' => $m->accepted_at?->toIso8601String(),
                'user' => $m->user ? [
                    'id' => $m->user->id,
                    'name' => $m->user->name,
                    'email' => $m->user->email,
                ] : null,
                'invite_token' => $m->status === 'invited' ? $m->invite_token : null,
            ]);

        return response()->json([
            'data' => $members,
            'limits' => [
                'max_members' => $this->gate->limit($business, 'max_members'),
                'used' => $business->memberships()->whereIn('status', ['active', 'invited'])->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $business = app('current.business');
        $this->assertOwnerOrAdmin();

        if (! $this->gate->checkLimit($business, 'max_members')) {
            return response()->json([
                'message' => 'Member limit reached for your current plan.',
                'limit' => $this->gate->limit($business, 'max_members'),
                'upgrade_required' => true,
            ], 422);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(['admin', 'staff'])],
        ]);

        $existing = User::where('email', $validated['email'])->first();

        if ($existing) {
            $already = $business->memberships()->where('user_id', $existing->id)->exists();
            if ($already) {
                return response()->json(['message' => 'User already belongs to this business.'], 422);
            }
        }

        $membership = DB::transaction(function () use ($validated, $existing, $business, $request) {
            return BusinessMembership::create([
                'business_id' => $business->id,
                'user_id' => $existing?->id,
                'email' => $validated['email'],
                'role' => $validated['role'],
                'status' => 'invited',
                'invite_token' => Str::random(48),
                'invited_at' => now(),
                'invited_by' => $request->user()->id,
            ]);
        });

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

        $this->gate->incrementUsage($business, 'max_members');

        return response()->json(['data' => $membership], 201);
    }

    public function update(Request $request, BusinessMembership $team): JsonResponse    {
        $business = app('current.business');
        $this->assertOwnerOrAdmin();

        if ($team->business_id !== $business->id) {
            abort(404, 'Membership not found.');
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'staff'])],
        ]);

        if ($team->role === 'owner' && $validated['role'] !== 'owner') {
            return response()->json(['message' => 'Ownership must be transferred through the ownership transfer workflow.'], 422);
        }

        $team->update(['role' => $validated['role']]);

        return response()->json(['data' => $team->fresh()]);
    }

    public function destroy(Request $request, BusinessMembership $team): JsonResponse
    {
        $business = app('current.business');
        $this->assertOwnerOrAdmin();

        if ($team->business_id !== $business->id) {
            abort(404, 'Membership not found.');
        }

        if ($team->role === 'owner') {
            return response()->json(['message' => 'The business owner cannot be removed. Transfer ownership first.'], 422);
        }

        $team->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoked_by' => $request->user()->id,
        ]);

        $this->gate->incrementUsage(app('current.business'), 'max_members', -1);

        return response()->json(['message' => 'Member access revoked.']);
    }

    protected function assertOwnerOrAdmin(): void
    {
        $business = app('current.business');
        $user = request()->user();

        if ($business->owner_id === $user->id) {
            return;
        }

        $membership = $business->memberships()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (! $membership || $membership->role !== 'admin') {
            abort(403, 'You are not allowed to manage the team.');
        }
    }
}
