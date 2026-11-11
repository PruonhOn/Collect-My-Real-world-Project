<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds the break-glass super administrator.
 *
 * Credentials come from the environment, so a real deployment never ends up
 * with a published default password. With nothing configured the account is not
 * created at all and the seeder says so: an account that cannot be signed into
 * is worse than no account, because it invites being left in place.
 */
class InitialAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->command?->warn(
                'No initial administrator created. Set ADMIN_EMAIL and ADMIN_PASSWORD, '
                .'then run: php artisan db:seed --class=InitialAdminSeeder'
            );

            return;
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                // Hashed by the `hashed` cast on the model.
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $user->assignRole('super-admin');

        $this->command?->info("Super administrator ready: {$user->email}");
    }
}
