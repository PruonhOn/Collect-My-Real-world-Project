<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\VesselResource;
use App\Models\Vessel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VesselController extends MasterDataController
{
    /** @return class-string<Model> */
    protected function modelClass(): string
    {
        return Vessel::class;
    }

    /** @return class-string<JsonResource> */
    protected function resourceClass(): string
    {
        return VesselResource::class;
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['name', 'imo_number', 'mmsi', 'flag_country'];
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return ['id', 'name', 'imo_number', 'capacity_teu', 'created_at'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'imo_number' => [
                'required',
                // Seven digits, and it must not start with 0: checkdigits aside,
                // IMO numbers are issued in the range 7000000-9899999.
                'regex:/^[1-9]\d{6}$/',
                ...$this->uniqueRules('imo_number', $model),
            ],
            'mmsi' => ['nullable', 'string', 'regex:/^\d{9}$/', ...$this->uniqueRules('mmsi', $model)],
            'flag_country' => ['nullable', 'string', 'size:2', 'alpha'],
            'vessel_type' => ['nullable', 'string', 'max:50'],
            'length_meters' => ['nullable', 'integer', 'between:1,999'],
            'beam_meters' => ['nullable', 'integer', 'between:1,999'],
            'capacity_teu' => ['nullable', 'integer', 'between:0,100000'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
