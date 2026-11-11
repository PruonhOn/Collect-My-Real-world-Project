<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class RoleService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createRole(array $data): Role
    {
        if (!isset($data['slug']) && isset($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        /** @var Role $role */
        $role = $this->repository->create($data);

        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        return $role->load('permissions');
    }

    public function updateRole(int $id, array $data): ?Role
    {
        if (!isset($data['slug']) && isset($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        /** @var Role|null $role */
        $role = $this->repository->update($id, $data);

        if ($role && isset($data['permissions']) && is_array($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        return $role?->load('permissions');
    }

    public function findByName(string $name): ?Role
    {
        /** @var Role|null */
        return $this->repository->findBy('name', $name);
    }

    public function findBySlug(string $slug): ?Role
    {
        /** @var Role|null */
        return $this->repository->findBy('slug', $slug);
    }

    public function syncPermissions(int $roleId, array $permissionIds): ?Role
    {
        /** @var Role|null $role */
        $role = $this->repository->find($roleId);

        if (!$role) {
            return null;
        }

        $role->permissions()->sync($permissionIds);

        return $role->load('permissions');
    }

    public function getRolesWithPermissions(): Collection
    {
        return $this->repository->all()->load('permissions');
    }

    public function getUsersByRole(int $roleId): Collection
    {
        /** @var Role|null $role */
        $role = $this->repository->find($roleId);

        if (!$role) {
            return collect();
        }

        return $role->users;
    }
}
