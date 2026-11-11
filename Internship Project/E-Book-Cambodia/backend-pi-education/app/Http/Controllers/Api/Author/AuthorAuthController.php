<?php

namespace App\Http\Controllers\Api\Author;

use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as PasswordRules;

class AuthorAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $author = Author::where('email', $validated['email'])->first();

        if (! $author || ! Hash::check($validated['password'], $author->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
                'errors' => ['email' => ['The provided credentials do not match our records.']],
            ], 401);
        }

        if ($author->isSuspended()) {
            return response()->json(['message' => 'Your account has been suspended.'], 403);
        }

        $author->forceFill(['last_login_at' => now()])->save();

        $token = $author->createToken('author-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'author' => $author->fresh(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['author' => $request->user()]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $author = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'school' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'social_links' => ['nullable'],
        ]);

        if (isset($validated['name'])) {
            $author->name = $validated['name'];
        }
        if (array_key_exists('phone', $validated)) {
            $author->phone = $validated['phone'];
        }
        if (array_key_exists('bio', $validated)) {
            $author->bio = $validated['bio'];
        }
        if (array_key_exists('specialization', $validated)) {
            $author->specialization = $validated['specialization'];
        }
        if (array_key_exists('organization', $validated)) {
            $author->organization = $validated['organization'];
        }
        if (array_key_exists('school', $validated)) {
            $author->school = $validated['school'];
        }

        if (array_key_exists('social_links', $validated) && $validated['social_links'] !== null && $validated['social_links'] !== '') {
            $links = $validated['social_links'];

            if (is_string($links)) {
                $decoded = json_decode($links, true);
                if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                    return $this->invalidSocialLinks('social_links must be a valid JSON object.');
                }
                $links = $decoded;
            }

            $socialValidator = Validator::make(['social_links' => $links], [
                'social_links' => ['array'],
                'social_links.*' => ['nullable', 'url:http,https', 'max:2048'],
            ]);

            if ($socialValidator->fails()) {
                return response()->json([
                    'message' => 'Invalid social_links.',
                    'errors' => $socialValidator->errors(),
                ], 422);
            }

            $author->social_links = $socialValidator->validated()['social_links'];
        }

        if ($request->hasFile('photo')) {
            if ($author->photo && ! str_starts_with($author->photo, 'http')) {
                Storage::disk('public')->delete($author->photo);
            }
            $author->photo = $request->file('photo')->store('author-photos', 'public');
        }

        $author->save();

        return response()->json(['author' => $author->fresh()]);
    }

    private function invalidSocialLinks(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => ['social_links' => [$message]],
        ], 422);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $author = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRules::defaults()],
        ]);

        if (! Hash::check($validated['current_password'], $author->password)) {
            return response()->json([
                'message' => 'The provided password does not match your current password.',
            ], 422);
        }

        $author->password = $validated['password'];
        $author->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }
}
