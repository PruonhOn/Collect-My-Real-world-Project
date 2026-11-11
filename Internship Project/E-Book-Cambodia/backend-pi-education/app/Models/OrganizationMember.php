<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class OrganizationMember extends Model
{
    protected $fillable = [
        'department_id', 'position_id', 'reports_to_id', 'name', 'email', 'phone',
        'photo', 'type', 'bio', 'sort_order', 'is_active', 'is_department_head',
    ];

    protected $appends = ['photo_url'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_department_head' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }
        if (str_starts_with($this->photo, 'http')) {
            return $this->photo;
        }

        return Storage::url($this->photo);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(OrganizationDepartment::class, 'department_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(OrganizationPosition::class, 'position_id');
    }

    public function reportsTo(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'reports_to_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(OrganizationMember::class, 'reports_to_id');
    }

    public function responsibilities(): HasMany
    {
        return $this->hasMany(OrganizationResponsibility::class, 'member_id')->orderBy('sort_order');
    }

    public function partner(): HasMany
    {
        return $this->hasMany(OrganizationPartner::class, 'member_id');
    }
}
