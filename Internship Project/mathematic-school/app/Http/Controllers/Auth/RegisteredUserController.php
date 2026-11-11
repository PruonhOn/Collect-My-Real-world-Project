<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['nullable', 'in:student,parent'],
        ]);

        $role = $request->role ?? 'student';

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $role,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($role);

        if ($role === 'student') {
            Student::create([
                'user_id' => $user->id,
                'student_id' => 'STU-' . strtoupper(Str::random(6)),
                'full_name' => $request->name,
                'date_of_birth' => $request->date_of_birth ?? now()->subYears(10),
                'age' => $request->age,
                'gender' => $request->gender ?? 'male',
                'phone' => $request->phone,
                'status' => 'active',
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard'));
    }
}
