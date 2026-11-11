<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('role') && $request->role) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->role));
        }

        $users = $query->latest()->paginate($request->per_page ?? 15);

        $mapped = $users->getCollection()->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar ? url('/storage/'.$user->avatar) : null,
            'is_active' => $user->is_active,
            'roles' => $user->roles->pluck('name'),
            'created_at' => $user->created_at->toDateTimeString(),
        ]);

        return $this->success([
            'users' => $mapped,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $user = User::with(['roles', 'purchases.book', 'reviews'])->find($id);

        if (! $user) {
            return $this->error('User not found', 404);
        }

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar ? url('/storage/'.$user->avatar) : null,
            'is_active' => $user->is_active,
            'roles' => $user->roles->pluck('name'),
            'purchases_count' => $user->purchases()->count(),
            'reviews_count' => $user->reviews()->count(),
            'total_spent' => (float) $user->purchases()->where('status', 'paid')->sum('amount'),
            'created_at' => $user->created_at->toDateTimeString(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $user = User::find($id);

        if (! $user) {
            return $this->error('User not found', 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'avatar' => ['sometimes', 'nullable', 'mimes:jpeg,png,webp,gif,svg', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->only(['name', 'email', 'phone', 'is_active']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        if ($request->has('role') && in_array($request->role, ['reader', 'admin'])) {
            $user->roles()->detach();
            $user->assignRole($request->role);
        }

        return $this->success($user->load('roles'), 'User updated successfully');
    }

    public function toggleActive(Request $request, int $id)
    {
        $user = User::find($id);

        if (! $user) {
            return $this->error('User not found', 404);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return $this->success(['is_active' => $user->is_active], 'User status updated');
    }

    public function destroy(Request $request, int $id)
    {
        if ($id === $request->user()->id) {
            return $this->error('You cannot delete your own account', 422);
        }

        $user = User::find($id);

        if (! $user) {
            return $this->error('User not found', 404);
        }

        $user->delete();

        return $this->success(null, 'User deleted successfully');
    }
}