<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';
}
