<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\PortResource;
use App\Models\Port;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortController extends MasterDataController
{
    /** @return class-string<Model> */
    protected function modelClass(): string
    {
        return Port::class;
    }

    /** @return class-string<JsonResource> */
    protected function resourceClass(): string
    {
        return PortResource::class;
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['un_locode', 'name', 'country_code'];
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return ['id', 'un_locode', 'name', 'country_code', 'created_at'];
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        parent::applyFilters($query, $request);

        $country = strtoupper(trim((string) $request->query('country_code', '')));

        if ($country !== '') {
            $query->where('country_code', $country);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model): array
    {
        return [
            'un_locode' => [
                'required',
                'string',
                // Two country letters plus two location letters, optionally
                // followed by a three character function code - SGSIN, DEHAM,
                // USNYC1. Case is normalised by the model.
                'regex:/^[A-Za-z]{2}[A-Za-z0-9]{3}([A-Za-z0-9]{3})?$/',
                ...$this->uniqueRules('un_locode', $model),
            ],
            'name' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2', 'alpha'],
            'port_type' => ['nullable', 'string', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'timezone' => ['nullable', 'string', 'max:64', 'timezone'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
