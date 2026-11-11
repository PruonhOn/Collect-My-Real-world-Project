<?php

namespace App\Repositories;

use App\Models\Branch;
use Illuminate\Support\Collection;

class BranchRepository extends BaseRepository
{
    public function __construct(Branch $branch)
    {
        parent::__construct($branch);
    }

    public function model(): Branch
    {
        return new Branch();
    }

    public function findByCode(string $code): ?Branch
    {
        return $this->findBy('code', $code);
    }

    public function findActive(): Collection
    {
        return $this->findAllBy('is_active', true);
    }
}
