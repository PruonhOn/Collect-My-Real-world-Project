<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\DriverResource;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;

class DriverController extends MasterDataController
{
    /** @return class-string<Model> */
    protected function modelClass(): string
    {
        return Driver::class;
    }

    /** @return class-string<JsonResource> */
    protected function resourceClass(): string
    {
        return DriverResource::class;
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['code', 'name', 'license_number', 'phone'];
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return ['id', 'code', 'name', 'license_expiry', 'created_at'];
    }

    /**
     * @return array<int, string>
     */
    protected function eagerLoads(): array
    {
        return ['carrier', 'truck'];
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

        // Backs the "licences expiring soon" screen the operations team runs.
        if ($request->has('expiring_within_days')) {
            $days = (int) $request->query('expiring_within_days');

            if ($days > 0) {
                $query->expiringWithin($days);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', ...$this->uniqueRules('code', $model)],
            'name' => ['required', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'nationality' => ['nullable', 'string', 'size:2', 'alpha'],
            'carrier_id' => ['nullable', 'integer', Rule::exists('carriers', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'truck_id' => ['nullable', 'integer', Rule::exists('trucks', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'license_expiry' => ['nullable', 'date'],
            'medical_expiry' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
