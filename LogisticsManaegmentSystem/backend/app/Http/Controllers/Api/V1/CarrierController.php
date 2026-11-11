<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CarrierType;
use App\Http\Resources\CarrierResource;
use App\Models\Carrier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;

class CarrierController extends MasterDataController
{
    /** @return class-string<Model> */
    protected function modelClass(): string
    {
        return Carrier::class;
    }

    /** @return class-string<JsonResource> */
    protected function resourceClass(): string
    {
        return CarrierResource::class;
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['code', 'name', 'scac', 'city', 'country_code'];
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return ['id', 'code', 'name', 'created_at'];
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        parent::applyFilters($query, $request);

        $type = (string) $request->query('type', '');

        if (CarrierType::tryFrom($type) !== null) {
            $query->where('type', $type);
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
            'scac' => ['nullable', 'string', 'size:4', 'alpha', ...$this->uniqueRules('scac', $model)],
            'type' => ['nullable', Rule::enum(CarrierType::class)],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
