<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['book_id', 'locale', 'title', 'description', 'content'];

    protected function casts(): array
    {
        return [
            'content' => 'string',
        ];
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
