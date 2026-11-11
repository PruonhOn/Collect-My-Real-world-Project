<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorTranslation extends Model
{
    protected $fillable = ['author_id', 'locale', 'name', 'biography'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}
