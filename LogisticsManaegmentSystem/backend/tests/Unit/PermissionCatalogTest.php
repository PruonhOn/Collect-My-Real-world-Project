<?php

namespace Tests\Unit;

use App\Support\PermissionCatalog;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * The permission inventory is the input to every authorization decision in the
 * system, so a mistake here is not a runtime 403 - it is a role quietly missing
 * a grant that nobody notices until a user cannot do their job.
 */
class PermissionCatalogTest extends TestCase
{
    public function test_every_permission_name_is_unique_and_sorted(): void
    {
        $names = PermissionCatalog::names();

        $this->assertSame(array_values(array_unique($names)), $names, 'Permission names must be unique.');
        $this->assertSame($names, $this->sorted($names), 'Permission names must be sorted.');
    }

    public function test_permissions_follow_the_resource_dot_action_shape(): void
    {
        foreach (PermissionCatalog::names() as $name) {
            $this->assertMatchesRegularExpression(
                '/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/',
                $name,
                "[{$name}] must be `<resource>.<action>` in lowercase snake case.",
            );
        }
    }

    public function test_star_expands_to_the_whole_inventory(): void
    {
        $this->assertSame(
            PermissionCatalog::names(),
            PermissionCatalog::permissionsForRole('super-admin'),
        );
    }

    public function test_a_resource_wildcard_grants_every_action_on_that_resource_only(): void
    {
        $granted = PermissionCatalog::permissionsForRole('operations-clerk');

        $this->assertContains('booking.create', $granted);
        $this->assertContains('booking.update', $granted);

        // Deletion is deliberately withheld from a clerk.
        $this->assertNotContains('booking.delete', $granted);

        // And a wildcard on one resource must not leak into another.
        $this->assertNotContains('invoice.create', $granted);
    }

    public function test_an_action_wildcard_grants_that_action_across_every_resource(): void
    {
        $granted = PermissionCatalog::permissionsForRole('viewer');

        $this->assertContains('invoice.view', $granted);
        $this->assertContains('carrier.view', $granted);
        $this->assertContains('dashboard.view', $granted);

        $this->assertNotContains('invoice.update', $granted);
        $this->assertNotContains('invoice.delete', $granted);
    }

    public function test_finance_cannot_reach_the_administration_group(): void
    {
        $granted = PermissionCatalog::permissionsForRole('finance-officer');

        $this->assertContains('invoice.create', $granted);
        $this->assertNotContains('user.view', $granted);
        $this->assertNotContains('role.update', $granted);
    }

    public function test_a_viewer_holds_no_mutating_permission_anywhere(): void
    {
        $mutating = array_values(array_filter(
            PermissionCatalog::names(),
            fn (string $name): bool => ! str_ends_with($name, '.view')
                && ! str_ends_with($name, '.download')
                && ! str_ends_with($name, '.export'),
        ));

        $this->assertNotEmpty($mutating, 'The inventory is expected to contain mutating permissions.');

        $granted = PermissionCatalog::permissionsForRole('viewer');

        foreach ($mutating as $name) {
            $this->assertNotContains($name, $granted, "[{$name}] must not be granted to a viewer.");
        }
    }

    public function test_every_role_receives_at_least_one_permission(): void
    {
        foreach (array_keys(PermissionCatalog::roles()) as $role) {
            $this->assertNotEmpty(
                PermissionCatalog::permissionsForRole($role),
                "Role [{$role}] resolves to no permissions at all.",
            );
        }
    }

    public function test_a_typo_in_a_role_definition_is_rejected_rather_than_ignored(): void
    {
        // The failure this prevents: `invocne.update` silently resolving to
        // nothing, leaving the role short a grant with no error anywhere.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invocne.update');

        PermissionCatalog::expand(['invocne.update']);
    }

    public function test_an_unknown_role_is_rejected_and_lists_the_real_ones(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown role [wizard]');

        PermissionCatalog::role('wizard');
    }

    public function test_groups_are_labelled_for_the_administration_screen(): void
    {
        foreach (PermissionCatalog::groups() as $group => $permissions) {
            $this->assertNotSame('', trim($group), 'A permission group must be labelled.');

            foreach ($permissions as $name => $label) {
                $this->assertNotSame('', trim($label), "[{$name}] must be labelled.");
            }
        }
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    private function sorted(array $names): array
    {
        $sorted = $names;
        sort($sorted);

        return $sorted;
    }
}
