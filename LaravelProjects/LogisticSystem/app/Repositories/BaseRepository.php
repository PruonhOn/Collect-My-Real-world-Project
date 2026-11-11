<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    abstract public function model(): Model;

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    public function findBy(string $column, mixed $value): ?Model
    {
        return $this->model->where($column, $value)->first();
    }

    public function findAllBy(string $column, mixed $value, array $columns = ['*']): Collection
    {
        return $this->model->where($column, $value)->get($columns);
    }

    public function findWhere(array $criteria): Collection
    {
        $query = $this->model->newQuery();

        foreach ($criteria as $column => $value) {
            if (is_array($value)) {
                $query->where($column, $value[0], $value[1]);
            } else {
                $query->where($column, $value);
            }
        }

        return $query->get();
    }

    public function getAll(array $filters = []): Collection
    {
        if (empty($filters)) {
            return $this->all();
        }

        $criteria = $filters;
        if (isset($criteria['search'])) {
            unset($criteria['search']);
        }

        if (!empty($criteria)) {
            return $this->findWhere($criteria);
        }

        return $this->all();
    }

    public function getById(int $id): ?Model
    {
        return $this->find($id);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Model
    {
        $record = $this->model->find($id);

        if ($record) {
            $record->update($data);
            return $record->fresh();
        }

        return null;
    }

    public function delete(int $id): bool
    {
        $record = $this->model->find($id);

        if ($record) {
            return $record->delete();
        }

        return false;
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    public function findWithTrash(int $id): ?Model
    {
        return $this->model->withTrashed()->find($id);
    }

    public function onlyTrashed(): Collection
    {
        return $this->model->onlyTrashed()->get();
    }

    public function restore(int $id): ?Model
    {
        $record = $this->model->withTrashed()->find($id);

        if ($record) {
            $record->restore();
            return $record;
        }

        return null;
    }

    public function forceDelete(int $id): bool
    {
        $record = $this->model->withTrashed()->find($id);

        if ($record) {
            return $record->forceDelete();
        }

        return false;
    }
}
