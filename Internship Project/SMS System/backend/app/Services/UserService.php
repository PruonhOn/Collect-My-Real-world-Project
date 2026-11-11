<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class UserService
{
    /**
     * Create a user with a random initial password and assign the given roles.
     *
     * @param  array<string, mixed>  $data
     * @param  string|array<int, string>  $roles
     */
    public function create(array $data, string|array $roles): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'] ?? Hash::make($this->randomPassword()),
            'is_active' => $data['is_active'] ?? true,
        ]);

        if ($data['password'] ?? false) {
            $user->password = $data['password'];
            $user->save();
        }

        $user->assignRole($roles);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $user->fill([
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'is_active' => $data['is_active'] ?? $user->is_active,
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        if (! empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user;
    }

    public function setActive(User $user, bool $active): void
    {
        $user->is_active = $active;
        $user->save();
    }

    public function markLogin(User $user): void
    {
        $user->last_login_at = now();
        $user->save();
    }

    public function randomPassword(int $length = 12): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnpqrstuvwxyz';
        $digits = '23456789';
        $pool = $upper.$lower.$digits;

        $password = $lower[random_int(0, strlen($lower) - 1)]
            .$upper[random_int(0, strlen($upper) - 1)]
            .$digits[random_int(0, strlen($digits) - 1)];

        for ($i = 3; $i < $length; $i++) {
            $password .= $pool[random_int(0, strlen($pool) - 1)];
        }

        return str_shuffle($password);
    }
}
