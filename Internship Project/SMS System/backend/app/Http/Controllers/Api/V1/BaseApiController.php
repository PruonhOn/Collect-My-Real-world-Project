<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class BaseApiController extends Controller
{
    protected const DEFAULT_PER_PAGE = 15;

    protected const MAX_PER_PAGE = 100;

    /**
     * Apply a keyword search to the query across the given columns, then
     * filter and paginate using consistent query parameters.
     *
     * @param  array<int, string>  $searchColumns
     * @param  array<string>  $filterable  attribute allow-list for filter[]
     */
    protected function paginate(Builder $query, Request $request, array $searchColumns = [], array $filterable = [], ?string $defaultOrderColumn = 'created_at', string $defaultOrderDirection = 'desc'): LengthAwarePaginator
    {
        $search = trim((string) $request->string('search'));

        if ($search !== '' && $searchColumns !== []) {
            $query->where(function (Builder $q) use ($searchColumns, $search) {
                foreach ($searchColumns as $column) {
                    $q->orWhere(fn (Builder $cq) => $this->applySearchToRelation($cq, $column, $search));
                }
            });
        }

        foreach ($request->input('filter', []) as $field => $value) {
            if ($value !== null && $value !== '' && is_string($field) && (in_array($field, $filterable, true) || $filterable === [] && $this->columnExists($query, $field))) {
                $this->applyFilterToRelation($query, (string) $field, $value);
            }
        }

        $sortColumn = $request->string('sort', $defaultOrderColumn);
        $sortDirection = $request->string('direction', $defaultOrderDirection)->lower() === 'asc' ? 'asc' : 'desc';

        if ($this->columnExists($query, (string) $sortColumn)) {
            $query->orderBy((string) $sortColumn, $sortDirection);
        } else {
            $query->orderBy($defaultOrderColumn, $defaultOrderDirection);
        }

        $perPage = max(1, min((int) $request->integer('per_page', self::DEFAULT_PER_PAGE), self::MAX_PER_PAGE));

        return $query->paginate($perPage);
    }

    private function columnExists(Builder $query, string $column): bool
    {
        return in_array($column, $query->getModel()->getConnection()->getSchemaBuilder()->getColumnListing($query->getModel()->getTable()), true);
    }

    /**
     * Apply a LIKE search to a column, resolving dotted relation paths
     * (e.g. "teacher.user.name") through nested whereHas constraints.
     */
    private function applySearchToRelation(Builder $query, string $column, string $search): Builder
    {
        if (! str_contains($column, '.')) {
            return $query->where($column, 'like', "%{$search}%");
        }

        $relation = Str::before($column, '.');
        $column = Str::after($column, '.');

        return $query->whereHas($relation, fn (Builder $q) => $this->applySearchToRelation($q, $column, $search));
    }

    /**
     * Apply an equality filter to a column, resolving dotted relation paths
     * (e.g. "user.is_active") through nested whereHas constraints.
     */
    private function applyFilterToRelation(Builder $query, string $column, mixed $value): Builder
    {
        if (! str_contains($column, '.')) {
            return $query->where($column, $value);
        }

        $relation = Str::before($column, '.');
        $column = Str::after($column, '.');

        return $query->whereHas($relation, fn (Builder $q) => $this->applyFilterToRelation($q, $column, $value));
    }

    /**
     * Standard pagination shape included in every list response envelope.
     *
     * @return array<string, mixed>
     */
    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
