<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRules;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', PasswordRules::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'role' => User::ROLE_READER,
        ]);

        $user->sendEmailVerificationNotification();

        return $this->respondWithToken($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
                'errors' => ['email' => ['The provided credentials do not match our records.']],
            ], 401);
        }

        return $this->respondWithToken($user);
    }

    public function googleLogin(Request $request): JsonResponse
    {
        $validated = $request->validate(['id_token' => ['required', 'string']]);
        $googleResponse = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $validated['id_token'],
        ]);

        if (! $googleResponse->successful()) {
            return response()->json(['message' => 'Invalid Google identity token.'], 401);
        }

        $google = $googleResponse->json();
        if (($google['aud'] ?? null) !== config('services.google.client_id') || ($google['email_verified'] ?? 'false') !== 'true') {
            return response()->json(['message' => 'Google token audience or verification is invalid.'], 401);
        }

        $email = $google['email'] ?? null;
        if (! is_string($email) || $email === '') {
            return response()->json(['message' => 'Google account email was not provided.'], 422);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            $user = User::create([
                'name' => $google['name'] ?? Str::before($email, '@'),
                'email' => $email,
                'avatar' => $google['picture'] ?? null,
                'password' => Str::random(64),
                'role' => User::ROLE_READER,
                'email_verified_at' => now(),
            ]);
        }

        return $this->respondWithToken($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'password' => ['sometimes', 'confirmed', PasswordRules::defaults()],
            'current_password' => ['required_with:password', 'string'],
        ]);

        if (isset($validated['password'])) {
            if (! Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'The provided password does not match your current password.',
                ], 422);
            }
            $user->password = $validated['password'];
        }

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (array_key_exists('phone', $validated)) {
            $user->phone = $validated['phone'];
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && ! str_starts_with($user->avatar, 'http')) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        return response()->json(['user' => $user->fresh()]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => __($status)]);
        }

        return response()->json(['message' => __($status)], 422);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', PasswordRules::defaults()],
        ]);

        $status = Password::reset($validated, function (User $user, string $password) {
            $user->forceFill(['password' => $password])->save();
            $user->tokens()->delete();
        });

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)]);
        }

        return response()->json(['message' => __($status)], 422);
    }

    private function respondWithToken(User $user, int $status = 200): JsonResponse
    {
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], $status);
    }
}
