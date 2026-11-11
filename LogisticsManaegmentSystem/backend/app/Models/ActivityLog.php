<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One entry in the activity log: an append-only record of what an authenticated
 * actor did, who they did it to, and when. Read by the Activity Logs screen;
 * never updated or deleted through the API.
 */
class ActivityLog extends Model
{
    use HasFactory;

    #[Fillable(['action', 'properties', 'ip_address', 'user_agent'])]
    protected $guarded = [];

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * The actor who performed the action.
     */
    public function causer(): MorphTo
    {
        return $this->morphTo('causer');
    }

    /**
     * The entity the action was performed on.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('subject');
    }
}
