<?php

namespace App\Support;

use App\Models\User;
use InvalidArgumentException;
use Spatie\Permission\Guard;

/**
 * Reads the permission inventory in config/permissions.php and answers the two
 * questions the rest of the system asks: what permissions exist, and which of
 * them does a given role hold.
 *
 * Roles in the config may use wildcards - `*`, `invoice.*`, `*.view`. They are
 * expanded here, at seed time, into concrete permission names. Spatie's own
 * wildcard support stays off (see config/permission.php), so every row in the
 * permissions table is a real, individually assignable permission and `can()`
 * is a plain lookup with no pattern matching at request time.
 */
final class PermissionCatalog
{
    /**
     * The guard name roles and permissions are created under.
     *
     * Resolved through Spatie's Guard helper rather than reading
     * `auth.defaults.guard` directly. Sanctum calls `Auth::shouldUse('sanctum')`
     * on an authenticated request, and Laravel 13 persists that choice into
     * `auth.defaults.guard`; reading it here would switch role lookups to
     * `sanctum` even though every role row is tagged `web`. Guard::getDefaultName
     * only considers guards configured for the User provider, so it stays on the
     * model's guard - the same guard Spatie uses for assignRole and friends.
     */
    public static function guard(): string
    {
        return Guard::getDefaultName(User::class);
    }

    /**
     * Every permission name in the inventory, sorted.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        $names = [];

        $groups = self::groups();
        foreach ($groups as $permissions) {
            foreach (array_keys($permissions) as $name) {
                $names[] = $name;
            }
        }

        $unique = array_values(array_unique($names, SORT_STRING));
        sort($unique, SORT_STRING);

        return $unique;
    }

    /**
     * Group label => (permission name => human label).
     *
     * Shaped for the Roles & Permissions screen, which renders one section per
     * group and one checkbox per permission.
     *
     * @return array<string, array<string, string>>
     */
    public static function groups(): array
    {
        /** @var array<string, array<string, string>> $groups */
        $groups = config('permissions.groups', []);

        return $groups;
    }

    /**
     * Role name => metadata, with permissions left as written in the config.
     *
     * @return array<string, array{label: string, description: string, permissions: array<int, string>}>
     */
    public static function roles(): array
    {
        /** @var array<string, array{label: string, description: string, permissions: array<int, string>}> $roles */
        $roles = config('permissions.roles', []);

        return $roles;
    }

    /**
     * Role metadata with every wildcard expanded against the inventory.
     *
     * @return array{label: string, description: string, permissions: array<int, string>}
     */
    public static function role(string $role): array
    {
        $roles = self::roles();

        if (! isset($roles[$role])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown role [%s]. Defined roles are: %s.',
                $role,
                $roles === [] ? 'none' : implode(', ', array_keys($roles)),
            ));
        }

        $definition = $roles[$role];

        $definition['permissions'] = self::expand($definition['permissions'], $role);

        return $definition;
    }

    /**
     * The concrete permission names a role holds, sorted.
     *
     * @return array<int, string>
     */
    public static function permissionsForRole(string $role): array
    {
        return self::role($role)['permissions'];
    }

    /**
     * Resolves a list of patterns to concrete permission names.
     *
     * A pattern is matched segment by segment against every known name, so
     * `*`, `invoice.*`, `*.view` and `*.*` all behave the way they read. A
     * pattern containing no wildcard has to match a permission that exists:
     * silently dropping `invocne.update` would leave a role quietly missing a
     * grant, and the symptom would only surface as a 403 in production.
     *
     * @param  array<int, string>  $patterns
     * @return array<int, string>
     */
    public static function expand(array $patterns, string $context = 'permissions'): array
    {
        $inventory = self::names();
        $resolved = [];

        foreach ($patterns as $pattern) {
            if (! is_string($pattern) || $pattern === '') {
                throw new InvalidArgumentException(sprintf(
                    'A %s entry must be a non-empty string, got [%s].',
                    $context,
                    get_debug_type($pattern),
                ));
            }

            $matches = [];
            $inventoryCount = count($inventory);
            for ($i = 0; $i < $inventoryCount; $i++) {
                $name = $inventory[$i];
                if (self::matches($pattern, $name)) {
                    $matches[] = $name;
                }
            }

            if ($matches === [] && ! str_contains($pattern, '*')) {
                throw new InvalidArgumentException(sprintf(
                    'The %s entry [%s] matches no permission in config/permissions.php.',
                    $context,
                    $pattern,
                ));
            }

            $matchCount = count($matches);
            for ($i = 0; $i < $matchCount; $i++) {
                $resolved[$matches[$i]] = true;
            }
        }

        $names = array_keys($resolved);
        sort($names);

        return $names;
    }

    /**
     * Segment-wise match of a pattern against a permission name.
     *
     * Deliberately not a regular expression: the pattern comes from config, and
     * a name containing a regex metacharacter must not be able to change the
     * meaning of the pattern around it.
     */
    private static function matches(string $pattern, string $name): bool
    {
        if ($pattern === '*') {
            return true;
        }

        $patternSegments = explode('.', $pattern);
        $nameSegments = explode('.', $name);

        if (count($patternSegments) !== count($nameSegments)) {
            return false;
        }

        $segmentCount = count($patternSegments);
        for ($i = 0; $i < $segmentCount; $i++) {
            $segment = $patternSegments[$i];
            if ($segment !== '*' && $segment !== $nameSegments[$i]) {
                return false;
            }
        }

        return true;
    }
}
