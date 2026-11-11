<?php
// ============================================================
// app/Models/User.php
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = ['name', 'email', 'password', 'is_active'];
    protected $hidden   = ['password', 'remember_token'];
    protected $casts    = ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean'];

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'assigned_to');
    }

    public function createdWorkOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'created_by');
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeTechnicians($q) { return $q->role('technician'); }

    public function getInitialsAttribute(): string
    {
        return strtoupper(collect(explode(' ', $this->name))->map(fn($w) => $w[0])->implode(''));
    }
}
