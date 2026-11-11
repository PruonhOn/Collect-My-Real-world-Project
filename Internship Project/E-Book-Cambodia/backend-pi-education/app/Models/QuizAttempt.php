<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'quiz_id', 'score', 'total', 'answers', 'passed', 'completed_at'])]
class QuizAttempt extends Model
{
    use HasFactory;

    protected $casts = [
        'score' => 'integer',
        'total' => 'integer',
        'answers' => 'array',
        'passed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function percent(): int
    {
        return $this->total > 0 ? (int) round(($this->score / $this->total) * 100) : 0;
    }
}
