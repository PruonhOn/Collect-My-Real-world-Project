<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * A user who cannot sign in.
     *
     * The account still exists and still passes email/password checks, so this
     * is the state App\Http\Middleware\EnsureUserIsActive and the login
     * controller both have to catch.
     */
    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * A user who has never signed in.
     */
    public function neverLoggedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_login_at' => null,
        ]);
    }

    /**
     * Grant one of the roles defined in config/permissions.php.
     *
     * Creates the role row on demand so a test need not run the seeder. Note
     * this grants the *role*, not its permissions: a role with no permission
     * rows behind it authorises nothing, so a test about authorization must
     * call TestCase::seedPermissions() first.
     */
    public function withRole(string $role): static
    {
        return $this->afterCreating(function (User $user) use ($role): void {
            Role::findOrCreate($role, PermissionCatalog::guard());
            $user->assignRole($role);
        });
    }
}
