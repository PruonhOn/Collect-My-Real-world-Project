<?php

namespace App\Services;

use App\Models\Branch;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class BranchService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createBranch(array $data): Branch
    {
        /** @var Branch */
        return $this->repository->create($data);
    }

    public function updateBranch(int $id, array $data): ?Branch
    {
        /** @var Branch|null */
        return $this->repository->update($id, $data);
    }

    public function findByCode(string $code): ?Branch
    {
        /** @var Branch|null */
        return $this->repository->findBy('code', $code);
    }

    public function getActiveBranches(): Collection
    {
        return $this->repository->findAllBy('is_active', true);
    }

    public function getBranchesWithStats(): Collection
    {
        return Branch::withCount([
            'warehouses',
            'vehicles',
            'drivers',
            'customers',
        ])->get();
    }

    public function toggleActive(int $id): ?Branch
    {
        /** @var Branch|null $branch */
        $branch = $this->repository->find($id);

        if (!$branch) {
            return null;
        }

        $branch->update(['is_active' => !$branch->is_active]);

        return $branch->fresh();
    }
}
