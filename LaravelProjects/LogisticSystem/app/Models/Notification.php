<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'type',
        'title',
        'message',
        'icon',
        'link',
        'is_read',
        'read_at',
        'notifiable_id',
        'notifiable_type',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function notifiable()
    {
        return $this->morphTo();
    }

    public function markAsRead(): void
    {
        if ($this->is_read === false) {
            $this->update(['is_read' => true, 'read_at' => now()]);
        }
    }

    public function markAsUnread(): void
    {
        $this->update(['is_read' => false, 'read_at' => null]);
    }
}
