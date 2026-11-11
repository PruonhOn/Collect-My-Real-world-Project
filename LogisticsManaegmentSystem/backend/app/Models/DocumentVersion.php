<?php

namespace App\Models;

use Database\Factories\DocumentVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One copy of one document's file.
 *
 * Not soft-deleted, unlike its parent. A file on its own is not a document
 * anybody can produce in a dispute - it is a byte string with no title, no
 * consignment and no dates - so the thing that is archived, and the thing that is
 * produced, is the `Document`. This row exists only to say "here is a copy, and
 * here is where it went".
 *
 * `disk` and `path` are stored rather than read from config on the way out, for
 * the same reason an invoice stores its currency: a disk that moved, or a failed
 * disk that had to be repointed, must not make the rows already on it unreadable.
 * Config decides where the next upload goes; this says where this one went.
 *
 * No `is_current` flag. The current copy is the highest `version_number` on the
 * parent, which is a rule over the rows and therefore cannot fall out of step with
 * them the way a maintained boolean can.
 */
#[Fillable([
    'document_id',
    'version_number',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size_bytes',
    'checksum',
    'uploaded_by_id',
])]
class DocumentVersion extends Model
{
    /** @use HasFactory<DocumentVersionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'size_bytes' => 'integer',
        ];
    }

    /**
     * The document this is a copy of.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Who uploaded it.
     *
     * Nullable: the column nulls when the account is removed, because a document
     * register that silently lost rows when somebody left the business would be
     * worse than one that says the uploader is no longer with us.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    /**
     * Whether the stored bytes are still where this row says they are.
     *
     * Worth asking before serving a download rather than after: a disk that was
     * cleared out of band, a restore from a backup that predates the upload, or a
     * deployment onto a volume that was not mounted. The alternative is a 500 from
     * the storage driver at the moment somebody needs the bill of lading, which
     * reads as the application being broken rather than as a missing file.
     */
    public function fileExists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }
}
