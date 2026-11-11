<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'book_id', 'last_page', 'total_pages', 'progress', 'last_read_at'])]
class ReadingProgress extends Model
{
    use HasFactory;

    protected $casts = [
        'last_page' => 'integer',
        'total_pages' => 'integer',
        'progress' => 'decimal:2',
        'last_read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
