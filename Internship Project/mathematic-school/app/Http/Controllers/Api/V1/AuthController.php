<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['អ៊ីមែល ឬពាក្យសម្ងាត់មិនត្រឹមត្រូវ។'],
            ]);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return response()->json(['message' => 'គណនីនេះត្រូវបានបិទ។'], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $profile = $this->getProfile($user);

        return response()->json([
            'message' => 'ចូលគណនីបានជោគជ័យ។',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
            ],
            'profile' => $profile,
            'token' => $token,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['nullable', 'in:student,parent'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['required_if:role,student', 'nullable', 'date'],
            'gender' => ['nullable', 'in:male,female'],
        ]);

        $role = $request->role ?? 'student';

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $role,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        $user->assignRole($role);

        if ($role === 'student') {
            $age = $request->date_of_birth ? calculate_age($request->date_of_birth) : null;

            Student::create([
                'user_id' => $user->id,
                'student_id' => 'STU-' . strtoupper(\Illuminate\Support\Str::random(6)),
                'full_name' => $request->name,
                'date_of_birth' => $request->date_of_birth,
                'age' => $age,
                'gender' => $request->gender ?? 'male',
                'phone' => $request->phone,
                'status' => 'active',
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'ចុះឈ្មោះបានជោគជ័យ។',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'បានចាកចេញ។']);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $this->getProfile($user);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
            ],
            'profile' => $profile,
        ]);
    }

    protected function getProfile(User $user): ?array
    {
        return match ($user->role) {
            'student' => $user->studentProfile ? [
                'id' => $user->studentProfile->id,
                'student_id' => $user->studentProfile->student_id,
                'full_name' => $user->studentProfile->full_name,
                'age' => $user->studentProfile->age,
                'gender' => $user->studentProfile->gender,
            ] : null,
            'teacher' => $user->teacherProfile ? [
                'id' => $user->teacherProfile->id,
                'full_name' => $user->teacherProfile->full_name,
                'specialization' => $user->teacherProfile->specialization,
            ] : null,
            'parent' => $user->parentProfile ? [
                'id' => $user->parentProfile->id,
                'full_name' => $user->parentProfile->full_name,
            ] : null,
            default => null,
        };
    }
}
