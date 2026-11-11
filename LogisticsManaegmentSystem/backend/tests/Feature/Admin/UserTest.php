<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * User administration: the accounts that can sign in.
 */
class UserTest extends AdminTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/users')
            ->assertUnauthorized();
    }

    public function test_it_refuses_a_caller_without_the_permission(): void
    {
        // Operations clerks hold no `user.*` grant, so the list is not theirs.
        $this->actingAsRole('operations-clerk')
            ->getJson('/api/v1/users')
            ->assertForbidden();
    }

    public function test_it_lists_users_with_their_roles(): void
    {
        User::factory()->withRole('finance-officer')->create(['name' => 'Fiona Ledger']);

        $this->actingAsAdmin()
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'is_active', 'roles']]]);
    }

    public function test_it_creates_a_user_with_a_password_and_roles(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/users', [
                'name' => 'New Clerk',
                'email' => 'New.Clerk@Example.com',
                'phone' => '  +971 50 000 0000  ',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'roles' => ['operations-clerk'],
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            // The email is lower-cased and the phone trimmed before storage.
            ->assertJsonPath('data.email', 'new.clerk@example.com')
            ->assertJsonPath('data.phone', '+971 50 000 0000')
            ->assertJsonPath('data.roles', ['operations-clerk']);

        $user = User::where('email', 'new.clerk@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('operations-clerk'));
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_an_email_cannot_be_reused_regardless_of_case(): void
    {
        User::factory()->create(['email' => 'ops@example.com']);

        $this->actingAsAdmin()
            ->postJson('/api/v1/users', [
                'name' => 'Impostor',
                'email' => 'OPS@Example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_a_new_password_must_be_confirmed(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/users', [
                'name' => 'Mismatch',
                'email' => 'mismatch@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'different-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_it_updates_only_the_fields_that_were_sent(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'password' => Hash::make('original-password'),
        ]);

        $this->actingAsAdmin()
            ->putJson('/api/v1/users/'.$user->id, ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            // Untouched, not nulled out, and the password survives an edit that
            // did not carry one.
            ->assertJsonPath('data.email', $user->email);

        $this->assertTrue(Hash::check('original-password', $user->refresh()->password));
    }

    public function test_it_changes_a_password_when_one_is_sent(): void
    {
        $user = User::factory()->create(['password' => Hash::make('original-password')]);

        $this->actingAsAdmin()
            ->putJson('/api/v1/users/'.$user->id, [
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('brand-new-password', $user->refresh()->password));
    }

    public function test_it_filters_by_role_and_searches(): void
    {
        User::factory()->withRole('finance-officer')->create(['name' => 'Fiona Ledger']);
        User::factory()->withRole('operations-clerk')->create(['name' => 'Oscar Clerk']);

        $this->actingAsAdmin()
            ->getJson('/api/v1/users?role=finance-officer')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Fiona Ledger');

        $this->actingAsAdmin()
            ->getJson('/api/v1/users?q=oscar')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Oscar Clerk');
    }

    public function test_it_deletes_a_user_and_revokes_their_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('spa');

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/users/'.$user->id)
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/users/'.$user->id)
            ->assertNotFound();
    }

    public function test_the_super_admin_cannot_be_deactivated(): void
    {
        $protected = User::factory()->withRole('super-admin')->create();

        $this->actingAsAdmin()
            ->putJson('/api/v1/users/'.$protected->id, ['is_active' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');

        $this->assertTrue($protected->refresh()->is_active);
    }

    public function test_the_super_admin_role_cannot_be_removed(): void
    {
        $protected = User::factory()->withRole('super-admin')->create();

        $this->actingAsAdmin()
            ->putJson('/api/v1/users/'.$protected->id, ['roles' => ['admin']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('roles');

        $this->assertTrue($protected->refresh()->hasRole('super-admin'));
    }

    public function test_the_super_admin_cannot_be_deleted(): void
    {
        $protected = User::factory()->withRole('super-admin')->create();

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/users/'.$protected->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user');

        $this->assertDatabaseHas('users', ['id' => $protected->id]);
    }

    public function test_a_user_cannot_delete_their_own_account(): void
    {
        $user = User::factory()->withRole('admin')->create();
        $token = $user->createToken('spa')->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/v1/users/'.$user->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user');
    }

    public function test_only_a_super_admin_may_grant_the_super_admin_role(): void
    {
        $this->actingAsRole('admin')
            ->postJson('/api/v1/users', [
                'name' => 'Would-be Root',
                'email' => 'root@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'roles' => ['super-admin'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('roles');
    }

    public function test_the_role_options_endpoint_lists_roles_with_labels(): void
    {
        $this->actingAsAdmin()
            ->getJson('/api/v1/users/roles')
            ->assertOk()
            ->assertJsonFragment(['name' => 'super-admin', 'label' => 'Super Administrator'])
            ->assertJsonFragment(['name' => 'viewer', 'label' => 'Viewer']);
    }

    public function test_the_role_options_endpoint_requires_the_permission(): void
    {
        $this->actingAsRole('operations-clerk')
            ->getJson('/api/v1/users/roles')
            ->assertForbidden();
    }
}
