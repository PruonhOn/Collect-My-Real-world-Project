<?php

namespace App\Services;

use App\Models\Permission;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class PermissionService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createPermission(array $data): Permission
    {
        if (!isset($data['slug']) && isset($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        /** @var Permission */
        return $this->repository->create($data);
    }

    public function updatePermission(int $id, array $data): ?Permission
    {
        if (!isset($data['slug']) && isset($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        /** @var Permission|null */
        return $this->repository->update($id, $data);
    }

    public function findBySlug(string $slug): ?Permission
    {
        /** @var Permission|null */
        return $this->repository->findBy('slug', $slug);
    }

    public function getByGroup(string $group): Collection
    {
        return $this->repository->findAllBy('group', $group);
    }

    public function getGroups(): Collection
    {
        return Permission::select('group')
            ->distinct()
            ->whereNotNull('group')
            ->pluck('group');
    }

    public function getPermissionsGrouped(): Collection
    {
        return Permission::all()->groupBy('group');
    }
}
