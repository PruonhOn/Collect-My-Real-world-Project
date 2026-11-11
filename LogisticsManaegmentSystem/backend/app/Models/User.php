<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'google_id', 'phone', 'avatar_path', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * `is_active` is cast to a real boolean rather than left as a MySQL tinyint
     * so the JSON envelope emits true/false, which is what the SPA's
     * CurrentUser type declares.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Canonicalise the fields a user is identified by. See Party::normalise() for
     * why this runs before validation as well as on save.
     *
     * Email is the one field the database enforces as unique, and a mail client
     * that title-cases the local part would otherwise let `Ops@Example.com` sail
     * past the check against a stored `ops@example.com` and fail on the index.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function normalise(array $attributes): array
    {
        $normalised = [];

        if (array_key_exists('email', $attributes)) {
            $normalised['email'] = strtolower(trim((string) $attributes['email']));
        }

        if (array_key_exists('name', $attributes)) {
            $normalised['name'] = trim((string) $attributes['name']);
        }

        if (array_key_exists('phone', $attributes)) {
            $normalised['phone'] = $attributes['phone'] === null
                ? null
                : trim((string) $attributes['phone']);
        }

        return $normalised;
    }

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            foreach (static::normalise($user->getAttributes()) as $column => $value) {
                $user->setAttribute($column, $value);
            }
        });
    }

    /**
     * Role names, sorted, for the session payload.
     *
     * @return array<int, string>
     */
    public function roleNames(): array
    {
        return $this->loadMissing('roles')
            ->roles
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Every permission this user effectively holds, whether granted directly or
     * through a role.
     *
     * This is the list the SPA uses to decide which nav entries and buttons to
     * render. It is a convenience, never a control: each endpoint re-checks the
     * permission server-side.
     *
     * @return array<int, string>
     */
    public function permissionNames(): array
    {
        return $this->loadMissing(['roles.permissions', 'permissions'])
            ->getAllPermissions()
            ->pluck('name')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
