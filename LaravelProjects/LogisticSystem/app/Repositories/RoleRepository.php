<?php

namespace App\Repositories;

use App\Models\Role;

class RoleRepository extends BaseRepository
{
    public function __construct(Role $role)
    {
        parent::__construct($role);
    }

    public function model(): Role
    {
        return new Role();
    }

    public function findBySlug(string $slug): ?Role
    {
        return $this->findBy('slug', $slug);
    }
}
