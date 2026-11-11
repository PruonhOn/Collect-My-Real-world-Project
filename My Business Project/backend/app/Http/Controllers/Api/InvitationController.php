<?php

namespace App\Http\Controllers\Api;

use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Handles accepting a business invitation.
 *
 * An invitation may be issued to an email address that has not registered
 * yet. When the invited person registers or signs in they exchange the token
 * from the invitation email for an active membership.
 */
class InvitationController extends Controller
{
    public function show(Request $request, string $token): JsonResponse
    {
        $membership = BusinessMembership::withoutGlobalScopes()
            ->where('invite_token', $token)
            ->with('business')
            ->first();

        if (! $membership) {
            return response()->json(['message' => 'This invitation is invalid or has expired.'], 404);
        }

        return response()->json([
            'data' => [
                'token' => $token,
                'status' => $membership->status,
                'role' => $membership->role,
                'email' => $membership->email,
                'business' => [
                    'id' => $membership->business->id,
                    'name' => $membership->business->name,
                ],
            ],
        ]);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $user = $request->user();

        $membership = BusinessMembership::withoutGlobalScopes()
            ->where('invite_token', $token)
            ->first();

        if (! $membership) {
            return response()->json(['message' => 'This invitation is invalid or has expired.'], 404);
        }

        if ($membership->status === 'revoked') {
            return response()->json(['message' => 'This invitation has been revoked.'], 422);
        }

        if ($membership->status === 'active') {
            return response()->json([
                'message' => 'This invitation has already been accepted.',
                'data' => $membership->fresh(),
            ], 422);
        }

        // An invitation belongs to exactly one email address. A signed-in user
        // may only accept an invitation that was addressed to them.
        if ($membership->email && mb_strtolower($membership->email) !== mb_strtolower((string) $user->email)) {
            return response()->json([
                'message' => 'This invitation was issued to a different email address.',
            ], 403);
        }

        $alreadyMember = BusinessMembership::withoutGlobalScopes()
            ->where('business_id', $membership->business_id)
            ->where('user_id', $user->id)
            ->where('id', '!=', $membership->id)
            ->exists();

        if ($alreadyMember) {
            return response()->json(['message' => 'You already belong to this business.'], 422);
        }

        $accepted = DB::transaction(function () use ($membership, $user) {
            $membership->update([
                'user_id' => $user->id,
                'email' => $user->email,
                'status' => 'active',
                'accepted_at' => now(),
                'invite_token' => null,
            ]);

            return $membership->fresh();
        });

        return response()->json(['message' => 'Invitation accepted.', 'data' => $accepted]);
    }
}
