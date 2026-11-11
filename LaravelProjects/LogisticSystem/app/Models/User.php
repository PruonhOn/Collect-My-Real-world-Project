<?php

namespace App\Models;

use App\Models\Traits\HasRoles;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'phone', 'avatar', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'user_branch');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function createdShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'created_by');
    }

    public function createdBranches(): HasMany
    {
        return $this->hasMany(Branch::class, 'created_by');
    }

    public function createdCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'created_by');
    }

    public function createdWarehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'created_by');
    }

    public function createdVehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'created_by');
    }

    public function createdVehicleMaintenances(): HasMany
    {
        return $this->hasMany(VehicleMaintenance::class, 'created_by');
    }

    public function createdDrivers(): HasMany
    {
        return $this->hasMany(Driver::class, 'created_by');
    }

    public function createdInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    public function createdPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'created_by');
    }

    public function createdTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    public function createdExpenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    public function createdInventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'created_by');
    }

    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(ShipmentDocument::class, 'uploaded_by');
    }

    public function assignedDeliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'assigned_by');
    }
}
