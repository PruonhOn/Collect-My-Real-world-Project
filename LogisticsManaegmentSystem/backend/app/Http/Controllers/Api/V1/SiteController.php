<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SiteKind;
use App\Http\Resources\SiteResource;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;

/**
 * Warehouses and factories, one controller and two surfaces.
 *
 * The kind comes from the route exactly as it does for Party, and `site_type`
 * plus `capacity_pallets` are only meaningful for a warehouse, so they are
 * rejected on a factory rather than silently stored and ignored.
 */
class SiteController extends MasterDataController
{
    /** @return class-string<Model> */
    protected function modelClass(): string
    {
        return Site::class;
    }

    /** @return class-string<JsonResource> */
    protected function resourceClass(): string
    {
        return SiteResource::class;
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['code', 'name', 'city', 'country_code', 'contact_name'];
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return ['id', 'code', 'name', 'city', 'created_at'];
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function applyScope(Builder $query, Request $request): void
    {
        $query->ofKind($this->siteKind($request));
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model): array
    {
        $kind = $this->siteKind($request);
        $table = Site::query()->getModel()->getTable();

        return [
            'code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique($table, 'code')
                    ->where(Site::KIND, $kind->value)
                    ->ignore($model?->getKey()),
            ],
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'site_type' => [$kind === SiteKind::Warehouse ? 'nullable' : 'prohibited', 'string', 'max:50'],
            'capacity_pallets' => [$kind === SiteKind::Warehouse ? 'nullable' : 'prohibited', 'integer', 'between:0,100000'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function withServerValues(array $attributes, Request $request): array
    {
        return $attributes + [Site::KIND => $this->siteKind($request)];
    }

    private function siteKind(Request $request): SiteKind
    {
        $value = $request->route()?->defaults['siteKind'] ?? null;

        return SiteKind::tryFrom((string) $value) ?? SiteKind::Warehouse;
    }
}
