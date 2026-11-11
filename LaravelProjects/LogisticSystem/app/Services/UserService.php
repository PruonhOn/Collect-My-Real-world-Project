<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createUser(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        /** @var User $user */
        $user = $this->repository->create($data);

        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->roles()->sync($data['roles']);
        }

        if (isset($data['branches']) && is_array($data['branches'])) {
            $user->branches()->sync($data['branches']);
        }

        return $user->load(['roles', 'branches']);
    }

    public function updateUser(int $id, array $data): ?User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        /** @var User|null $user */
        $user = $this->repository->update($id, $data);

        if ($user && isset($data['roles']) && is_array($data['roles'])) {
            $user->roles()->sync($data['roles']);
        }

        if ($user && isset($data['branches']) && is_array($data['branches'])) {
            $user->branches()->sync($data['branches']);
        }

        return $user?->load(['roles', 'branches']);
    }

    public function findByEmail(string $email): ?User
    {
        /** @var User|null */
        return $this->repository->findBy('email', $email);
    }

    public function assignRole(int $userId, int|array $roleIds): ?User
    {
        /** @var User|null $user */
        $user = $this->repository->find($userId);

        if (!$user) {
            return null;
        }

        $roleIds = is_array($roleIds) ? $roleIds : [$roleIds];
        $user->roles()->syncWithoutDetaching($roleIds);

        return $user->load('roles');
    }

    public function removeRole(int $userId, int $roleId): ?User
    {
        /** @var User|null $user */
        $user = $this->repository->find($userId);

        if (!$user) {
            return null;
        }

        $user->roles()->detach($roleId);

        return $user->load('roles');
    }

    public function syncRoles(int $userId, array $roleIds): ?User
    {
        /** @var User|null $user */
        $user = $this->repository->find($userId);

        if (!$user) {
            return null;
        }

        $user->roles()->sync($roleIds);

        return $user->load('roles');
    }

    public function searchUsers(?string $keyword = null, ?bool $isActive = null): Collection
    {
        $query = $this->repository->findWhere([]);

        if ($keyword) {
            $query = $this->repository->model()
                ->where('name', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%")
                ->orWhere('phone', 'like', "%{$keyword}%")
                ->get();
        }

        if ($isActive !== null) {
            $query = $query->where('is_active', $isActive);
        }

        return $query;
    }

    public function getActiveUsers(): Collection
    {
        return $this->repository->findAllBy('is_active', true);
    }

    public function getUsersByBranch(int $branchId): Collection
    {
        return User::whereHas('branches', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })->get();
    }

    public function toggleActive(int $id): ?User
    {
        /** @var User|null $user */
        $user = $this->repository->find($id);

        if (!$user) {
            return null;
        }

        $user->update(['is_active' => !$user->is_active]);

        return $user->fresh();
    }
}
