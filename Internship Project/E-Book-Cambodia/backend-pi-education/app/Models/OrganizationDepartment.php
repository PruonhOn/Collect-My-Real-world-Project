<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationDepartment extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function positions(): HasMany
    {
        return $this->hasMany(OrganizationPosition::class, 'department_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class, 'department_id');
    }
}
