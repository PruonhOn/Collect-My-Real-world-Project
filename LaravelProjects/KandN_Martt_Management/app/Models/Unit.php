<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'short_name',
        'operator',
        'operation_value',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'operation_value' => 'decimal:2',
            'status' => 'string',
        ];
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
