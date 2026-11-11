<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['fileable_type', 'fileable_id', 'disk', 'path', 'original_name', 'mime_type', 'size', 'type', 'uploaded_by'])]
class File extends Model
{
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
