<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookTranslation extends Model
{
    protected $fillable = [
        'book_id',
        'locale',
        'title',
        'description',
        'content',
        'meta_title',
        'meta_description',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
