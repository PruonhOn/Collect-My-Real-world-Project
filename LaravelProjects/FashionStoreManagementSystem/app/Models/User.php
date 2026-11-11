<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'role' => Role::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isManager(): bool
    {
        return $this->role === Role::Manager;
    }

    public function isCashier(): bool
    {
        return $this->role === Role::Cashier;
    }

    public function hasRole(Role|string ...$roles): bool
    {
        return in_array(
            $this->role,
            array_map(
                fn (Role|string $role) => $role instanceof Role ? $role : Role::from($role),
                $roles
            ),
            true
        );
    }

    public function roleLabel(): string
    {
        return $this->role?->label() ?? '';
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'user_id');
    }
}
