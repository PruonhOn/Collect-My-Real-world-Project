<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttemptAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'selected_answer_ids',
        'is_correct',
    ];

    protected $casts = [
        'selected_answer_ids' => 'array',
        'is_correct' => 'boolean',
    ];

    public function attempt()
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    public function question()
    {
        return $this->belongsTo(QuizQuestion::class);
    }
}
