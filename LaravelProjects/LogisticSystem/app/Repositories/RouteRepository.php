<?php

namespace App\Repositories;

use App\Models\Route;

class RouteRepository extends BaseRepository
{
    public function __construct(Route $route)
    {
        parent::__construct($route);
    }

    public function model(): Route
    {
        return new Route();
    }

    public function findByCode(string $code): ?Route
    {
        return $this->findBy('code', $code);
    }
}
