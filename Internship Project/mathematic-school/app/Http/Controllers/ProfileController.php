<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        if ($request->filled('current_password') || $request->filled('new_password')) {
            $request->validate([
                'current_password' => ['required', 'current_password'],
                'new_password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user->update(['password' => Hash::make($request->new_password)]);
        }

        return back()->with('success', __('messages.profile') . ' — ' . __('messages.updated_successfully'));
    }
}
