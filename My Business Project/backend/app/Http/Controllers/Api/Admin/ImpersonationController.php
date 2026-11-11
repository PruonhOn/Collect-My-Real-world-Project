<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Models\ImpersonationLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $admin = $request->user();

        if ($admin->id === $user->id) {
            return response()->json(['message' => 'You cannot impersonate yourself.'], 422);
        }

        $log = ImpersonationLog::create([
            'impersonator_id' => $admin->id,
            'impersonated_id' => $user->id,
            'reason' => $validated['reason'],
            'started_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Auth::guard('web')->login($user);

        return response()->json([
            'data' => [
                'impersonation_id' => $log->id,
                'user' => $user->only(['id', 'name', 'email']),
            ],
        ]);
    }

    public function stop(Request $request): JsonResponse
    {
        $log = ImpersonationLog::whereNull('ended_at')
            ->where('impersonator_id', $request->user()->id)
            ->latest('started_at')
            ->first();

        if (! $log) {
            return response()->json(['message' => 'No active impersonation session found.'], 404);
        }

        $log->update(['ended_at' => now()]);

        $admin = User::findOrFail($log->impersonator_id);
        Auth::guard('web')->login($admin);

        return response()->json(['message' => 'Impersonation ended.']);
    }
}
