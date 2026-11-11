<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\BusinessSubscription;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        // Create default business
        $slug = Str::slug($validated['name'] . '-business-' . Str::random(6));
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => $validated['name'] . "'s Business",
            'slug' => $slug,
            'currency' => 'USD',
            'timezone' => config('app.timezone', 'UTC'),
            'is_active' => true,
        ]);

        BusinessMembership::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
            'accepted_at' => now(),
        ]);

        // Assign free plan
        $freePlan = Plan::where('slug', 'free')->first();
        if ($freePlan) {
            BusinessSubscription::create([
                'business_id' => $business->id,
                'plan_id' => $freePlan->id,
                'status' => 'active',
                'starts_at' => now(),
            ]);
        }

        $token = $user->createToken('spa-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'business' => $business,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Account is disabled.'], 403);
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('spa-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('ownedBusinesses', 'memberships'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }
}
