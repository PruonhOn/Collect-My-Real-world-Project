<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Auth\ChangePasswordRequest;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    public function __construct(
        private readonly UserService $users,
        private readonly AuditService $audit,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        if ($user === null || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account has been deactivated. Contact the administrator.'],
            ]);
        }

        $device = $request->string('device_name')->toString() ?: 'web';

        $token = $user->createToken($device)->plainTextToken;
        $this->users->markLogin($user);
        $this->audit->record('auth.login', $user);

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => (new UserResource($user->load(['roles', 'teacher', 'student', 'staff', 'parentProfile'])))->resolve($request),
            ],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->user()->currentAccessToken()?->delete();

        if ($user !== null) {
            $this->audit->record('auth.logout', $user);
        }

        return response()->json([
            'data' => ['message' => 'Logged out.'],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles', 'teacher', 'student', 'staff', 'parentProfile', 'avatar']);

        return response()->json([
            'data' => new UserResource($user),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->password = $request->string('new_password')->toString();
        $user->save();

        $request->user()->currentAccessToken()?->delete();
        $this->audit->record('auth.password_changed', $user);

        return response()->json([
            'data' => ['message' => 'Password updated. Please log in again.'],
            'meta' => null,
            'errors' => null,
        ]);
    }
}
