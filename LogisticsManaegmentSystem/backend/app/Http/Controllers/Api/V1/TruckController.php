<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\TruckResource;
use App\Models\Truck;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;

class TruckController extends MasterDataController
{
    /** @return class-string<Model> */
    protected function modelClass(): string
    {
        return Truck::class;
    }

    /** @return class-string<JsonResource> */
    protected function resourceClass(): string
    {
        return TruckResource::class;
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['plate_number', 'code', 'make', 'model'];
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return ['id', 'plate_number', 'code', 'capacity_kg', 'created_at'];
    }

    /**
     * @return array<int, string>
     */
    protected function eagerLoads(): array
    {
        return ['carrier'];
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        parent::applyFilters($query, $request);

        if ($carrierId = $request->integer('carrier_id')) {
            $query->where('carrier_id', $carrierId);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model): array
    {
        return [
            'plate_number' => [
                'required', 'string', 'max:30', 'regex:/^[A-Za-z0-9 -]+$/',
                ...$this->uniqueRules('plate_number', $model),
            ],
            'code' => ['nullable', 'string', 'max:50'],
            // A vehicle can be onboarded before a carrier is assigned, so this
            // is optional - but if given it has to point at a live carrier.
            'carrier_id' => ['nullable', 'integer', Rule::exists('carriers', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'truck_type' => ['nullable', Rule::in(['tractor', 'rigid', 'box_truck', 'flatbed', 'reefer', 'tanker'])],
            'capacity_kg' => ['nullable', 'integer', 'between:0,200000'],
            'capacity_pallets' => ['nullable', 'integer', 'between:0,60'],
            'make' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'year' => ['nullable', 'string', 'regex:/^(19|20)\d{2}$/'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
