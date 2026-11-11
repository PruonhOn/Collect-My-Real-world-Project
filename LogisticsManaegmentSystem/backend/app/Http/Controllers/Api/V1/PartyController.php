<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PartyType;
use App\Http\Resources\PartyResource;
use App\Models\Party;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Customers, shippers and consignees.
 *
 * One controller, three surfaces. The kind of party is read from the route's
 * `partyType` default and written with forceFill, so the type of a record can
 * only ever come from the endpoint it was created through.
 */
class PartyController extends MasterDataController
{
    /** @return class-string<Model> */
    protected function modelClass(): string
    {
        return Party::class;
    }

    /** @return class-string<JsonResource> */
    protected function resourceClass(): string
    {
        return PartyResource::class;
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['code', 'name', 'contact_name', 'city', 'country_code'];
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
        $query->ofType($this->partyType($request));
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
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9._-]+$/',
                $this->uniqueCodeRule($request, $model),
            ],
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'tax_number' => ['nullable', 'string', 'max:100'],
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

    /**
     * The unique index is on (type, code), so uniqueness has to be scoped to the
     * type or every other customer would collide with the shipper of the same
     * short code.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function withServerValues(array $attributes, Request $request): array
    {
        return $attributes + [Party::TYPE => $this->partyType($request)];
    }

    /**
     * The unique index is on (type, code), so uniqueness has to be scoped to the
     * type or a customer would collide with a shipper that happens to share its
     * short code. A plain `unique:parties,code` would also be stricter than the
     * database and would reject a genuinely valid record.
     *
     * Soft-deleted rows are deliberately still in scope: they hold their code in
     * the index, so validating against them is what stops the database from
     * throwing a raw integrity error at the client.
     */
    private function uniqueCodeRule(Request $request, ?Model $model): Unique
    {
        return Rule::unique('parties', 'code')
            ->where(Party::TYPE, $this->partyType($request)->value)
            ->ignore($model?->getKey());
    }

    private function partyType(Request $request): PartyType
    {
        $value = $request->route()?->defaults['partyType'] ?? null;

        return PartyType::tryFrom((string) $value) ?? PartyType::Customer;
    }
}
