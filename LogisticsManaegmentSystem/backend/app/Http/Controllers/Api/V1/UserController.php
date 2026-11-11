<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ManagedUserResource;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\PermissionCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Administration of the accounts that can sign in.
 *
 * Not master data: a user has no `code`, is not referenced by a picker, and is
 * the one record the system must never be left without - so the lifecycle is
 * its own. It extends RecordController for the list/search/paginate machinery
 * and nothing else; the writes are overridden because a user carries a role set
 * and a password, neither of which is a plain column.
 *
 * Authorization is declared per route, as everywhere else. The super-admin
 * guards below are policy, not permissions: `user.update` is held by the
 * `admin` role too, and it must not be enough to demote or delete the account
 * the system uses to get back in.
 */
class UserController extends RecordController
{
    /**
     * The break-glass role. Cannot be removed from a user, and its holders
     * cannot be deactivated or deleted.
     */
    private const PROTECTED_ROLE = 'super-admin';

    /** @return class-string<Model> */
    protected function modelClass(): string
    {
        return User::class;
    }

    /** @return class-string<JsonResource> */
    protected function resourceClass(): string
    {
        return ManagedUserResource::class;
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['name', 'email', 'phone'];
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return ['id', 'name', 'email', 'is_active', 'last_login_at', 'created_at'];
    }

    /**
     * `roles` is read by the resource for every row, so it is eager loaded on
     * the listing rather than fetched once per row under `preventLazyLoading`.
     *
     * @return array<int, string>
     */
    protected function eagerLoads(): array
    {
        return ['roles'];
    }

    protected function hasActiveFlag(): bool
    {
        return true;
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        parent::applyFilters($query, $request);

        $role = trim((string) $request->query('role', ''));

        if ($role !== '') {
            $query->whereHas('roles', fn (Builder $inner) => $inner->where('name', $role));
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model): array
    {
        $rolesTable = (string) config('permission.table_names.roles', 'roles');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', ...$this->uniqueRules('email', $model)],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],

            // Required on create, optional on update: an edit that leaves the
            // password out keeps the current one, while `confirmed` still
            // demands a matching `password_confirmation` whenever a new one is
            // sent.
            'password' => $model === null
                ? ['required', 'string', 'min:8', 'confirmed']
                : ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],

            'roles' => ['sometimes', 'array'],
            'roles.*' => [
                'string',
                Rule::exists($rolesTable, 'name')->where('guard_name', PermissionCatalog::guard()),
            ],
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $attributes = $this->validated($request, null);

        $roles = $attributes['roles'] ?? [];
        unset($attributes['roles']);

        $this->guardRoleAssignment($roles, $request);

        $user = User::query()->create($attributes);
        $user->syncRoles($roles);

        return ApiResponse::created(
            $this->present($user->refresh()),
            'Record created.',
            '/'.$request->path().'/'.$user->getRouteKey(),
        );
    }

    public function update(Request $request, int|string $record): JsonResponse
    {
        $user = $this->find($request, $record);

        $attributes = $this->validated($request, $user);

        $rolesProvided = array_key_exists('roles', $attributes);
        $roles = $attributes['roles'] ?? [];
        unset($attributes['roles']);

        // A blank password on an edit means "leave it alone", not "set it to
        // empty" - `nullable` lets the form resubmit an untouched field.
        if (array_key_exists('password', $attributes) && ($attributes['password'] === null || $attributes['password'] === '')) {
            unset($attributes['password']);
        }

        $this->guardProtectedAccount($request, $user, $attributes, $rolesProvided, $roles);

        $user->fill($attributes)->save();

        if ($rolesProvided) {
            $user->syncRoles($roles);
        }

        return ApiResponse::success($this->present($user->refresh()), 'Record updated.');
    }

    public function destroy(Request $request, int|string $record): JsonResponse
    {
        $user = $this->find($request, $record);

        if ($user->hasRole(self::PROTECTED_ROLE)) {
            throw ValidationException::withMessages([
                'user' => 'The super administrator account cannot be deleted.',
            ]);
        }

        if ($request->user()?->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own account.',
            ]);
        }

        // A hard delete - the users table has no soft-delete column - so the
        // tokens go first, or a signed-in browser would keep a token whose user
        // row no longer exists.
        $user->tokens()->delete();
        $user->delete();

        return ApiResponse::success(null, 'Record deleted.');
    }

    /**
     * The role options the form's multi-select offers.
     *
     * Registered behind `user.view` on its own route rather than under the
     * `users/{record}` shape, because "roles" would otherwise be read as a
     * record id.
     */
    public function roles(): JsonResponse
    {
        $configured = PermissionCatalog::roles();

        $roles = Role::query()
            ->orderBy('name')
            ->pluck('name')
            ->map(fn (string $name): array => [
                'name' => $name,
                'label' => $configured[$name]['label'] ?? ucfirst(str_replace(['-', '_'], ' ', $name)),
            ])
            ->values()
            ->all();

        return ApiResponse::success($roles, 'Roles retrieved.');
    }

    /**
     * @param  array<int, string>  $roles
     */
    private function guardRoleAssignment(array $roles, Request $request): void
    {
        $protected = self::PROTECTED_ROLE;
        if (is_array($roles) && in_array($protected, $roles, true) && ! $request->user()?->hasRole($protected)) {
            throw ValidationException::withMessages([
                'roles' => 'Only a super administrator may grant the super administrator role.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $roles
     */
    private function guardProtectedAccount(
        Request $request,
        User $user,
        array $attributes,
        bool $rolesProvided,
        array $roles,
    ): void {
        $protected = self::PROTECTED_ROLE;
        if ($user->hasRole($protected)) {
            if (array_key_exists('is_active', $attributes) && ! $attributes['is_active']) {
                throw ValidationException::withMessages([
                    'is_active' => 'The super administrator account cannot be deactivated.',
                ]);
            }

            if ($rolesProvided && is_array($roles) && ! in_array($protected, $roles, true)) {
                throw ValidationException::withMessages([
                    'roles' => 'The super administrator role cannot be removed.',
                ]);
            }
        }

        if ($request->user()?->is($user) && array_key_exists('is_active', $attributes) && ! $attributes['is_active']) {
            throw ValidationException::withMessages([
                'is_active' => 'You cannot deactivate your own account.',
            ]);
        }

        $this->guardRoleAssignment($roles, $request);
    }
}
