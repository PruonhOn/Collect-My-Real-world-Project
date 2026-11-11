<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;

/**
 * Shared CRUD behaviour for a single-table API resource.
 *
 * Every module in this API is a paginated, filterable, searchable list plus
 * show/store/update/delete, and they were about to be the same eight controller
 * methods written once per module. The differences that are genuinely
 * per-resource - the search columns, the validation rules, the resource that
 * shapes the JSON, and any scope that restricts which rows a surface may see -
 * are the extension points below. Anything not overridden behaves identically
 * across modules on purpose, so the API cannot drift between them.
 *
 * This is deliberately *not* named for master data. Master data is the lookup
 * tables other modules reference; shipments move, and their controller is not a
 * lookup table controller that happens to be reused.
 *
 * Authorisation is NOT handled here. It is declared per route, because a
 * controller that quietly checked permissions would make the permission column
 * in routes/api.php a lie.
 */
abstract class RecordController extends Controller
{
    /**
     * The Eloquent model this controller owns.
     *
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * The resource that defines this resource's JSON contract.
     *
     * @return class-string<JsonResource>
     */
    abstract protected function resourceClass(): string;

    /**
     * Validation rules for one write. `$model` is null on create.
     *
     * @return array<string, mixed>
     */
    abstract protected function rules(Request $request, ?Model $model): array;

    /**
     * Columns searched by the `q` query parameter.
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['code', 'name'];
    }

    /**
     * Columns the `sort` query parameter accepts.
     *
     * The request is never allowed to pass a sort column through unvalidated: an
     * attacker-controlled column name is a way to probe which columns and
     * indexes exist, and a bad one is a 500 rather than a helpful 422.
     *
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return ['id', 'code', 'name', 'created_at'];
    }

    /**
     * Relations eager loaded on the listing endpoint.
     *
     * `preventLazyLoading` is on outside production, so a relation read inside a
     * resource that is not listed here raises instead of quietly issuing one
     * query per row.
     *
     * @return array<int, string>
     */
    protected function eagerLoads(): array
    {
        return [];
    }

    /**
     * Relations counted onto every row, exposed as `{relation}_count`.
     *
     * Separate from `eagerLoads()` because the two are answered by different SQL:
     * `with()` fetches the related rows, `withCount()` folds them into a single
     * correlated subquery and leaves them behind. A module that needs the size of a
     * collection rather than its contents - how many versions a document has, how
     * many payments a charge has been settled by - wants the count, and listing
     * the full collection to count it in PHP would pull every row of every
     * one-to-many onto a paginated response.
     *
     * Declared rather than derived from the eager loads for the same reason they
     * are: a relation that is eagerly loaded is not necessarily one that should
     * also appear in the JSON.
     *
     * @return array<int, string>
     */
    protected function countedRelations(): array
    {
        return [];
    }

    /**
     * Whether this resource carries an `is_active` flag.
     *
     * Master data does; a shipment does not, because "active" is meaningless for
     * something that either moves or does not - it has a status and a lifecycle
     * instead. The flag is opt-in rather than inferred from the schema so that
     * adding the column later cannot silently change a resource's filter
     * behaviour.
     */
    protected function hasActiveFlag(): bool
    {
        return false;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->query('per_page', 25), 100));

        $sort = (string) $request->query('sort', 'id');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, $this->sortable(), true)) {
            $sort = 'id';
        }

        $query = $this->modelClass()::query();

        $this->applyScope($query, $request);
        $this->applyFilters($query, $request);

        $paginator = $query
            ->with($this->eagerLoads())
            ->withCount($this->countedRelations())
            ->orderBy($sort, $direction)
            ->paginate($perPage);

        // Items come pre-shaped through the resource, the counts from the
        // paginator. Both have to be passed explicitly: handing
        // `ApiResponse::paginated()` a ResourceCollection instead would leave it
        // with nothing to read the counts from and `meta` would come back null.
        return ApiResponse::paginated(
            $paginator,
            'Records retrieved.',
            $this->resourceClass()::collection($paginator->getCollection())->resolve(),
        );
    }

    public function show(Request $request, int|string $record): JsonResponse
    {
        return ApiResponse::success(
            $this->present($this->find($request, $record)),
            'Record retrieved.',
        );
    }

    public function store(Request $request): JsonResponse
    {
        $model = $this->modelClass()::query()->create(
            $this->withServerValues($this->validated($request, null), $request),
        );

        return ApiResponse::created(
            $this->present($model->refresh()),
            'Record created.',
            // Root-relative, and built from the request path rather than a route
            // name so the shared tables, which serve several surfaces from one
            // controller, point at the surface the caller actually used.
            '/'.$request->path().'/'.$model->getRouteKey(),
        );
    }

    public function update(Request $request, int|string $record): JsonResponse
    {
        $model = $this->find($request, $record);

        $model->fill(
            $this->withServerValues($this->validated($request, $model), $request),
        )->save();

        return ApiResponse::success(
            $this->present($model->refresh()),
            'Record updated.',
        );
    }

    public function destroy(Request $request, int|string $record): JsonResponse
    {
        $this->find($request, $record)->delete();

        // 200 rather than 204: the envelope is part of this API's contract, and a
        // bodyless response would force the SPA to special-case this endpoint.
        return ApiResponse::success(null, 'Record deleted.');
    }

    /**
     * Shape a record for the response, with its relations loaded.
     *
     * Every endpoint goes through here on purpose. A resource that uses
     * `whenLoaded` produces one shape on the listing and a different one on show
     * or store, so a client that reads `carrier` off a list row finds the key
     * missing after it creates the very same record. Loading here keeps the
     * contract identical whichever endpoint answered.
     *
     * @return array<string, mixed>
     */
    protected function present(Model $model): array
    {
        return $this->resourceClass()::make(
            $model->load($this->eagerLoads())->loadCount($this->countedRelations()),
        )->resolve();
    }

    /**
     * Restrict a lookup to the records this surface owns.
     *
     * Scoping only the listing is not enough. A module that serves several
     * surfaces from one table has to refuse a record belonging to a different
     * one on show, update and delete as well, or /customers/7 happily edits a
     * shipper that happens to have id 7.
     */
    protected function find(Request $request, int|string $key): Model
    {
        $query = $this->modelClass()::query();

        $this->applyScope($query, $request);

        // findOrFail, so a record that exists but belongs to another surface is
        // indistinguishable from one that does not exist at all.
        return $query->findOrFail($key);
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function applyScope(Builder $query, Request $request): void
    {
        //
    }

    /**
     * Validate the payload.
     *
     * The model's canonical form is merged in first, so the `unique` rules
     * compare the value that is about to be stored rather than whatever casing
     * the client happened to send. Normalising only on save would let `maeu`
     * sail past a check against a stored `MAEU` and fail on the database index
     * with a 500.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Model $model = null): array
    {
        $request->merge($this->modelClass()::normalise($request->all()));

        return $request->validate($this->relaxed($this->rules($request, $model), $model));
    }

    /**
     * Make `required` mean "required if you sent it" on an update.
     *
     * PATCH sends only the fields being changed. Without this, an update of a
     * single field fails validation for every other required field, which makes
     * partial updates impossible to express.
     *
     * `sometimes` has to be a separate array element rather than the string
     * 'sometimes|required': ValidationRuleParser only splits a bare string on
     * ':', so a piped string is read as the name of a custom rule and blows up
     * with "Method Validator::validateSometimes|required does not exist".
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function relaxed(array $rules, ?Model $model): array
    {
        if ($model === null) {
            return $rules;
        }

        foreach ($rules as $key => $ruleList) {
            if (! is_array($ruleList)) {
                continue;
            }

            $relaxed = [];

            foreach ($ruleList as $rule) {
                if ($rule === 'required') {
                    $relaxed[] = 'sometimes';
                }

                $relaxed[] = $rule;
            }

            $rules[$key] = $relaxed;
        }

        return $rules;
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        $this->applySearch($query, $request);

        // `is_active` is tri-state: absent means "both", so a caller can list
        // everything or narrow to live records only.
        if ($this->hasActiveFlag() && $request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
    }

    /**
     * Match the `q` parameter against the searchable columns.
     *
     * Everything a search matches has to go inside one group. An override that
     * added its own `where(...)` would be ANDed with the group built here, so a
     * consignment matching on reference but not on any partner name would be
     * dropped - the two conditions would have to both hold rather than either.
     * That is why relations are added through the hook below instead.
     *
     * @param  Builder<Model>  $query
     */
    protected function applySearch(Builder $query, Request $request): void
    {
        $search = trim((string) $request->query('q', ''));

        if ($search === '') {
            return;
        }

        $columns = $this->searchable();

        $query->where(function (Builder $inner) use ($columns, $search): void {
            foreach ($columns as $column) {
                $inner->orWhere($column, 'like', '%'.$search.'%');
            }

            $this->applyRelationSearch($inner, $search);
        });
    }

    /**
     * Add related records to the same search group.
     *
     * @param  Builder<Model>  $inner  an OR group, already open
     */
    protected function applyRelationSearch(Builder $inner, string $search): void
    {
        //
    }

    /**
     * Add the values the server owns and the client never sends.
     *
     * The discriminator on a shared table is the important case: a client posting
     * to /shippers cannot smuggle `type: customer` past the route, because the
     * route decides it, not the payload.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function withServerValues(array $attributes, Request $request): array
    {
        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function uniqueRules(string $column, ?Model $model): array
    {
        $table = $this->modelClass()::query()->getModel()->getTable();

        return $model === null
            ? ['unique:'.$table.','.$column]
            : ['unique:'.$table.','.$column.','.$model->getKey()];
    }

    /**
     * A date rule that has to compare against a field the caller may not have
     * sent.
     *
     * `after_or_equal:etd` is the obvious way to express "arrival is not before
     * departure", and it is wrong here. A PATCH that revises the ETA on its own
     * does not carry the ETD, so the rule compares against a null and rejects the
     * update - the client cannot correct one date without resending the other.
     * The stored value is used when the request leaves the other field out, and
     * the comparison is skipped entirely when there is nothing to compare against,
     * because an ETA on a shipment with no planned departure is not a contradiction.
     *
     * @return array<int, mixed>
     */
    protected function onOrAfter(Request $request, ?Model $model, string $field, string $other): array
    {
        $rules = ['sometimes', 'nullable', 'date'];

        $reference = $request->exists($other)
            ? $request->input($other)
            : $model?->getAttribute($other);

        if ($reference === null) {
            return $rules;
        }

        $rules[] = Rule::date()->afterOrEqual(
            $reference instanceof DateTimeInterface
                ? $reference->format('Y-m-d')
                : (string) $reference,
        );

        return $rules;
    }
}
