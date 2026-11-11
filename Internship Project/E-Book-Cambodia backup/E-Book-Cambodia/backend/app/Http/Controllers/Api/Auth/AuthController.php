<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('reader');

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->success([
            'user' => $user->load('roles'),
            'token' => $token,
        ], 'Account created successfully', 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials', 401);
        }

        if (! $user->is_active) {
            return $this->error('Your account has been deactivated.', 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->success([
            'user' => $user->load('roles'),
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully');
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('roles');

        return $this->success($user);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];

        if ($request->hasFile('avatar')) {
            $rules['avatar'] = ['required', 'mimes:jpeg,png,webp,gif,svg', 'max:5120'];
        } else {
            $rules['avatar'] = ['sometimes', 'nullable', 'string', 'max:1000'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->only(['name', 'phone']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } elseif ($request->has('avatar') && ! is_null($request->input('avatar'))) {
            $data['avatar'] = $request->input('avatar');
        }

        $user->update($data);

        return $this->success($user->load('roles'), 'Profile updated successfully');
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect', 422, [
                'current_password' => ['Current password is incorrect'],
            ]);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return $this->success(null, 'Password changed successfully');
    }
}
