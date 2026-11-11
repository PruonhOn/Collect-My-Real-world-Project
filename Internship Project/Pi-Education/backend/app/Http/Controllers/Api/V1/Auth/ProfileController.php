<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return $this->success(
            new UserResource($request->user()->load('roles')),
            'Profile retrieved.'
        );
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->safe()->except(['avatar']);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');

            if ($user->avatar && $user->avatar !== $path) {
                Storage::disk('public')->delete($user->avatar);
            }

            $data['avatar'] = $path;
        }

        $user->update($data);

        return $this->success(
            new UserResource($user->fresh()->load('roles')),
            'Profile updated successfully.'
        );
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->validated('current_password'), $user->password)) {
            return $this->error('Current password is incorrect.', null, 422);
        }

        $user->update(['password' => $request->validated('new_password')]);

        $request->user()->currentAccessToken()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->success([
            'token' => $token,
        ], 'Password changed successfully. Please sign in again with your new password.');
    }
}
