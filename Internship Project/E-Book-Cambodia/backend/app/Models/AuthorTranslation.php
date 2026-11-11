<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthorTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['author_id', 'locale', 'name', 'bio'];

    public function author()
    {
        return $this->belongsTo(Author::class);
    }
}
