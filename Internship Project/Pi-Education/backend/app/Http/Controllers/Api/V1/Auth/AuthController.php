<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'password' => $request->validated('password'),
            'locale' => $request->header('X-Locale', config('app.locale')),
        ]);

        $user->assignRole(User::ROLE_USER);

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => new UserResource($user->load('roles')),
        ], 'Registration successful.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return $this->error('Invalid credentials.', null, 401);
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            return $this->error('Your account is inactive. Contact support.', null, 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => new UserResource($user->load('roles')),
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');

        return $this->success(new UserResource($user), 'Current user retrieved.');
    }
}
