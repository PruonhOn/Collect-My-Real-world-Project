<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id', 'name', 'slug', 'business_type', 'country', 'currency', 'timezone',
        'logo_path', 'address', 'phone', 'email', 'website', 'is_active', 'is_suspended',
        'suspended_at', 'suspended_by', 'suspension_reason', 'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_suspended' => 'boolean',
        'suspended_at' => 'datetime',
        'settings' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(BusinessMembership::class);
    }

    public function activeMemberships(): HasMany
    {
        return $this->hasMany(BusinessMembership::class)->where('status', 'active');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(BusinessSubscription::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(BusinessInvoice::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function usageCounters(): HasMany
    {
        return $this->hasMany(UsageCounter::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(BusinessActivityLog::class);
    }
}
