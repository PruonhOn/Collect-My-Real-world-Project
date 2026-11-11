<?php

namespace App\Repositories;

use App\Models\Permission;
use Illuminate\Support\Collection;

class PermissionRepository extends BaseRepository
{
    public function __construct(Permission $permission)
    {
        parent::__construct($permission);
    }

    public function model(): Permission
    {
        return new Permission();
    }

    public function findBySlug(string $slug): ?Permission
    {
        return $this->findBy('slug', $slug);
    }

    public function findByGroup(string $group): Collection
    {
        return $this->findAllBy('group', $group);
    }
}
