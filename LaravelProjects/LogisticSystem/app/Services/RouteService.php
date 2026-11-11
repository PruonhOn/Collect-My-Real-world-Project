<?php

namespace App\Services;

use App\Models\Route;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class RouteService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createRoute(array $data): Route
    {
        if (isset($data['waypoints']) && is_array($data['waypoints'])) {
            $data['waypoints'] = json_encode($data['waypoints']);
        }

        /** @var Route */
        return $this->repository->create($data);
    }

    public function updateRoute(int $id, array $data): ?Route
    {
        if (isset($data['waypoints']) && is_array($data['waypoints'])) {
            $data['waypoints'] = json_encode($data['waypoints']);
        }

        /** @var Route|null */
        return $this->repository->update($id, $data);
    }

    public function findByCode(string $code): ?Route
    {
        /** @var Route|null */
        return $this->repository->findBy('code', $code);
    }

    public function getActiveRoutes(): Collection
    {
        return $this->repository->findAllBy('is_active', true);
    }

    public function toggleActive(int $id): ?Route
    {
        /** @var Route|null $route */
        $route = $this->repository->find($id);

        if (!$route) {
            return null;
        }

        $route->update(['is_active' => !$route->is_active]);

        return $route->fresh();
    }
}
