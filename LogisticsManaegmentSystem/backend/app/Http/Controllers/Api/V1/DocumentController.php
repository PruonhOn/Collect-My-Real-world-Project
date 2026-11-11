<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DocumentType;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Support\ApiResponse;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use JsonException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The document register: what paperwork the business holds, and how to get it back.
 *
 * CRUD is inherited, with three things overridden and one added.
 *
 * `store` and `update` exist because a document is not describable without its
 * file. Every other module in this API is a row of scalars; this one is a row plus
 * a byte stream, and the rule that makes them one thing rather than two is that a
 * document always has a current version. So an upload and its description are
 * written together, inside one transaction, and an update that carries no file
 * keeps the copy it already has.
 *
 * `destroy` is overridden for the same reason, in the other direction: deleting
 * the row has to delete the bytes, because the whole reason the file is not a
 * public asset is that the database row is the only thing keeping track of it. A
 * row removed from the table with its file orphaned on disk is a file with no
 * permission, no audit trail and no way to be found again.
 *
 * `download` is the reason this module is not just another CRUD screen. The bytes
 * are served by the API, through `document.download` - a permission held
 * separately from `document.view`, so somebody can be shown that a bill of lading
 * exists without being able to open it. Nothing here hands out a URL.
 */
class DocumentController extends RecordController
{
    /**
     * @return class-string<Document>
     */
    protected function modelClass(): string
    {
        return Document::class;
    }

    /**
     * @return class-string<DocumentResource>
     */
    protected function resourceClass(): string
    {
        return DocumentResource::class;
    }

    /**
     * What somebody has in their hand when they type.
     *
     * The title is ours and `reference` is the carrier's or the shipper's. Both
     * are here for the same reason the expense module searches on its own reference
     * and the vendor's name: the person searching is reading a number off an email
     * or off the top of a PDF, not recalling what we called the file.
     *
     * `shipment_id` is not searchable on its own - a number nobody types - but the
     * consignment reference is, and the hook below adds it to the same group. A
     * document register is read by asking whose paperwork this is at least as often
     * as by asking what it is called.
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return [
            Document::TITLE,
            Document::REFERENCE,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return [
            'id',
            Document::TITLE,
            Document::DOCUMENT_TYPE,
            Document::ISSUED_ON,
            Document::EXPIRES_ON,
            'created_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function eagerLoads(): array
    {
        return [
            'shipment',
            'currentVersion',
            'currentVersion.uploader',
        ];
    }

    /**
     * How many copies a document has.
     *
     * The register shows the current file's version number, and a row reading
     * "version 4" with no idea whether four uploads happened or forty is not
     * something anybody can act on - it is the number that tells an operator an
     * amendment went in after the copy the customer already has.
     *
     * @return array<int, string>
     */
    protected function countedRelations(): array
    {
        return ['versions'];
    }

    /**
     * @param  Builder<Document>  $inner
     */
    protected function applyRelationSearch(Builder $inner, string $search): void
    {
        $inner->orWhereHas('shipment', fn (Builder $shipment): Builder => $shipment
            ->where('reference', 'like', '%'.$search.'%'));
    }

    /**
     * @param  Builder<Document>  $query
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        parent::applyFilters($query, $request);

        if ($type = $request->query(Document::DOCUMENT_TYPE)) {
            $query->where(Document::DOCUMENT_TYPE, $type);
        }

        if ($request->filled('shipment_id')) {
            $query->where('shipment_id', $request->integer('shipment_id'));
        }

        /*
         * The two expiry questions, kept apart.
         *
         * `expiring` and `expired` are separate filters rather than one because
         * they are asked for different reasons: one is "what do I need to renew",
         * the other is "what is already worthless". `expiring` also excludes what
         * has already lapsed, so the two lists never overlap and neither has to be
         * read twice to find the half that matters.
         */
        if ($request->boolean('expiring')) {
            $query->expiringWithin((int) $request->integer(
                'expiring_days',
                (int) config('logistics.documents.expiry_warning_days', 30),
            ));
        }

        if ($request->boolean('expired')) {
            $query->expired();
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            Document::TITLE => ['required', 'string', 'max:255'],

            Document::DOCUMENT_TYPE => ['required', Rule::enum(DocumentType::class)],

            /*
             * Nullable and not unique - the migration explains why, and the
             * short version is that one shipper's invoice number covers twenty
             * documents in a single export file.
             */
            Document::REFERENCE => ['sometimes', 'nullable', 'string', 'max:60'],

            /*
             * Optional, because most of this table is not about a consignment:
             * the fleet insurance certificate belongs to the business. `nullOnDelete`
             * in the schema is the other half of that - the paperwork outlives the
             * consignment, because the consignment is what was filed against.
             */
            'shipment_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('shipments', 'id')->whereNull('deleted_at'),
            ],

            Document::ISSUED_ON => ['sometimes', 'nullable', 'date'],

            /*
             * On or after the issue date, through the shared helper rather than an
             * `after_or_equal:issued_on` string: a PATCH that corrects the expiry on
             * its own does not carry the issue date, and a rule comparing against an
             * absent field rejects the correction the client is trying to make.
             */
            Document::EXPIRES_ON => $this->onOrAfter($request, $model, Document::EXPIRES_ON, Document::ISSUED_ON),

            /*
             * Required on create, optional on update.
             *
             * That asymmetry is the whole file lifecycle in one rule, and it comes
             * free from `RecordController::relaxed()`: `required` becomes `sometimes`
             * on an update, so sending no file leaves the current copy alone and
             * sending one appends a new version. It also means there is no way to
             * clear a document's file, which is correct - a document with no file is
             * not a document.
             *
             * The type and size allowlists are read from config rather than written
             * here, so the 20 MB and the permitted mime types are the numbers
             * `config/logistics.php` already documents instead of a second copy that
             * could drift. `File` inspects the file's actual contents, not the
             * extension the browser claimed for it.
             */
            'file' => [
                'sometimes',
                ...$this->fileRules(),
            ],

            /*
             * A direct-to-S3 upload is finalised by sending the token the presign
             * endpoint issued instead of the bytes themselves. The token is opaque,
             * short-lived, bound to the current user and, for an amendment, to the
             * document it was issued for.
             */
            'upload_token' => ['sometimes', 'string'],

            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * The validated payload with the file taken out of it.
     *
     * `file` is a request key and not a column, so it has to leave before the
     * attributes reach `create()` or `fill()`. Mass assignment is strict in this
     * application - an unknown attribute is an error rather than a silently dropped
     * key - which is the right default everywhere else and the reason this override
     * exists: the upload is handled by `appendVersion`, not by Eloquent, and the
     * exception it raises here would otherwise be a 500 for a mistake the client
     * could do nothing about.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Model $model = null): array
    {
        $attributes = parent::validated($request, $model);

        unset($attributes['file'], $attributes['upload_token']);

        return $attributes;
    }

    /**
     * Upload a document: its description and its first copy, together.
     */
    public function store(Request $request): JsonResponse
    {
        $upload = $this->resolveUpload($request, null);

        if ($upload === null) {
            throw ValidationException::withMessages([
                'file' => ['The file field is required.'],
            ]);
        }

        try {
            $document = DB::transaction(function () use ($request, $upload): Document {
                $document = Document::query()->create(
                    $this->withServerValues($this->validated($request), $request),
                );

                $this->appendVersion(
                    $document,
                    $upload['file'],
                    $request->user()?->getAuthIdentifier(),
                );

                return $document;
            });
        } finally {
            $this->discardPendingUpload($upload);
        }

        return ApiResponse::created(
            $this->present($document),
            'Document uploaded.',
            '/'.$request->path().'/'.$document->getRouteKey(),
        );
    }

    /**
     * Edit a document's details, and optionally replace its file.
     *
     * Same shape as the inherited update - validate, fill, save - with the file
     * appended inside the same transaction. Both halves are one act: a PATCH that
     * changes the expiry and uploads the reissued certificate has to leave the
     * register showing a certificate with the new expiry and the new file, and
     * failing between them would leave a document that says it is valid until 2028
     * with 2026's paper attached.
     */
    public function update(Request $request, int|string $record): JsonResponse
    {
        $document = $this->find($request, $record);
        $upload = $this->resolveUpload($request, $document);

        try {
            DB::transaction(function () use ($request, $document, $upload): void {
                $document->fill(
                    $this->withServerValues($this->validated($request, $document), $request),
                )->save();

                if ($upload !== null) {
                    $this->appendVersion(
                        $document,
                        $upload['file'],
                        $request->user()?->getAuthIdentifier(),
                    );
                }
            });
        } finally {
            $this->discardPendingUpload($upload);
        }

        return ApiResponse::success(
            $this->present($document->refresh()),
            'Document updated.',
        );
    }

    /**
     * Issue a short-lived presigned URL for a new document's file.
     *
     * The browser uploads straight to object storage, which keeps the bytes out
     * of Vercel's 4.5 MB request limit. The token returned here is what the
     * ordinary create endpoint accepts in place of a multipart `file`; it is
     * bound to this user, this action and, when amending, this document.
     */
    public function uploadUrl(Request $request): JsonResponse
    {
        return $this->presignUpload($request, null);
    }

    /**
     * Issue a presigned URL for a replacement copy of an existing document.
     */
    public function uploadUrlForDocument(Request $request, int|string $record): JsonResponse
    {
        return $this->presignUpload($request, $this->find($request, $record));
    }

    /**
     * Remove a document and every copy of it.
     *
     * The inherited delete only archives the row, and for this module that is not a
     * complete answer: a row is the only record of where its bytes are, so an
     * archived document whose file is still on the disk is unreachable by the
     * application and still occupies storage forever - nothing in this application
     * restores an archive, so "someday somebody brings it back" is not a plan.
     *
     * So the files go, and so do the version rows that name them. The parent is
     * still soft-deleted, keeping this endpoint's meaning the same as every other
     * record's delete in the API, but the two halves agree with each other: no row
     * is left claiming a file that no longer exists. `document_versions` has a
     * cascading foreign key, but that only fires on a hard delete of the parent, so
     * the rows have to be removed here.
     *
     * Bytes first. A failure part way through leaves rows describing files that
     * still exist - visibly wrong, and re-runnable - where the reverse order would
     * leave bytes on a disk that no longer records where they are, which is the
     * outcome the version table exists to make impossible.
     */
    public function destroy(Request $request, int|string $record): JsonResponse
    {
        $document = $this->find($request, $record);

        DB::transaction(function () use ($document): void {
            $document->versions()->get()->each(function (DocumentVersion $version): void {
                Storage::disk($version->disk)->delete($version->path);

                $version->forceDelete();
            });

            $document->delete();
        });

        return ApiResponse::success(null, 'Document deleted.');
    }

    /**
     * Serve the current copy of a document's file.
     *
     * A stream from the private disk under the name the file arrived with, so the
     * recipient's browser saves it as the carrier called it rather than as a
     * path segment of ours. Guarded by `document.download` at the route, which is
     * held separately from `document.view` precisely so a bill of lading can be
     * listed without being opened.
     *
     * The two failures that are not 404s are handled explicitly, because both are
     * things an operator can act on and neither is obvious from a 500:
     *
     * - no version rows at all is a plain 404. The validation rules make it
     *   impossible to create one, so it means the document is not here.
     * - the row exists but the bytes do not is a 409, not a 404 and not a 500. The
     *   document is real and readable - every other field on it is fine - so
     *   "not found" would send somebody looking for the document itself, and a
     *   plain 404 is rendered with generic wording precisely so that a 404 never
     *   leaks detail. This is a conflict with the document's own state: somebody
     *   cleared the disk, or restored from a backup older than the upload, and the
     *   fix is to upload the file again.
     */
    public function download(Request $request, int|string $record): StreamedResponse|RedirectResponse
    {
        $document = $this->find($request, $record);
        $version = $document->currentVersion;

        abort_if($version === null, 404);

        abort_unless(
            $version->fileExists(),
            409,
            'The file for this document is no longer on the storage disk. Upload it again.',
        );

        $disk = Storage::disk($version->disk);

        /*
         * On object storage, hand the browser a presigned GET instead of streaming
         * the bytes through the function. The redirect itself is tiny, so a file
         * larger than Vercel's 4.5 MB response limit still downloads. The URL is
         * still issued only after `document.download` has been checked; the
         * permission is not delegated to the bucket.
         */
        if ($disk->providesTemporaryUrls() && $version->disk !== 'local') {
            $url = $disk->temporaryUrl(
                $version->path,
                now()->addMinutes((int) config('logistics.documents.download_url_ttl_minutes', 5)),
                [
                    'ResponseContentDisposition' => HeaderUtils::makeDisposition(
                        HeaderUtils::DISPOSITION_ATTACHMENT,
                        $version->original_name,
                        Str::ascii($version->original_name) ?: 'document',
                    ),
                    'ResponseContentType' => $version->mime_type ?: 'application/octet-stream',
                ],
            );

            return redirect()->away($url);
        }

        return $disk->download($version->path, $version->original_name);
    }

    /**
     * Store an uploaded file as the document's next version.
     *
     * Three steps that have to agree: the version number is one above the current
     * maximum, the bytes go under that number, and the row records where they went.
     * Called from inside the caller's transaction, so a failure to write the file
     * leaves neither the row nor the document behind.
     *
     * `$file->store()` puts the file under a name Laravel generates rather than
     * under the one the browser sent. That is deliberate and it is the security
     * property that matters here: `original_name` is stored for display and for
     * the download's filename, and is never allowed to contribute a single
     * character of a path, so a file called `../../../.env` cannot write outside
     * the document's own directory.
     *
     * `reorder()` on the version-number lookup: the `versions()` relation carries
     * `orderBy('version_number')` for reading, and an aggregate that inherits it
     * produces `max(version_number) ... order by version_number` - accepted by
     * SQLite and rejected outright by PostgreSQL, which demands that an ordered
     * column be grouped. The ordering is meaningless on a scalar aggregate anyway.
     */
    private function appendVersion(
        Document $document,
        UploadedFile $file,
        int|string|null $uploadedById,
    ): DocumentVersion {
        $number = ((int) $document->versions()->reorder()->max('version_number')) + 1;

        // Read these before storing: a real upload may be moved by the storage
        // adapter, and the checksum has to describe the bytes that were written.
        $size = $file->getSize();
        $checksum = hash_file('sha256', (string) $file->getRealPath());

        $path = $file->store('documents/'.$document->getKey(), $this->disk());

        $version = $document->versions()->create([
            'version_number' => $number,
            'disk' => $this->disk(),
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $size,
            'checksum' => $checksum,
            'uploaded_by_id' => $uploadedById,
        ]);

        $this->pruneOlderVersionsThan($document);

        return $version;
    }

    /**
     * Drop the oldest copies once a document passes its retention cap.
     *
     * `logistics.documents.max_versions_per_document` is a storage bound, not a
     * reason to refuse an amendment: an operator correcting a scan has to be able
     * to upload whatever they have, and quietly refusing would leave them re-uploading
     * to a failure with no explanation. So the oldest copies go, and the newest
     * `cap` are kept.
     *
     * The newest, because that is what the download serves and what an operator
     * will assume is there. A bill of lading amended four times and then trimmed to
     * its last three keeps the amended copy and loses two intermediates nobody was
     * going to produce - a register that kept the oldest instead would look
     * complete while answering questions with a superseded file.
     *
     * `reorder()` for the same reason as in `appendVersion`, and here it is not
     * merely cosmetic: the relation's ascending `orderBy` and this method's
     * descending one would otherwise both be in the query, the first would win, and
     * the prune would keep exactly the copies it was meant to remove.
     */
    private function pruneOlderVersionsThan(Document $document): void
    {
        $cap = (int) config('logistics.documents.max_versions_per_document', 10);

        if ($cap < 1) {
            return;
        }

        $keep = $document->versions()
            ->reorder()
            ->orderByDesc('version_number')
            ->limit($cap)
            ->pluck('id');

        $document->versions()
            ->whereNotIn('id', $keep)
            ->get()
            ->each(function (DocumentVersion $version): void {
                Storage::disk($version->disk)->delete($version->path);

                $version->delete();
            });
    }

    /**
     * Issue a presigned upload and the token that finalises it.
     */
    private function presignUpload(Request $request, ?Document $document): JsonResponse
    {
        $data = $request->validate([
            'file_name' => ['required', 'string', 'max:255'],
            'mime_type' => ['required', 'string', 'max:128'],
            'size_bytes' => ['required', 'integer', 'min:1', 'max:'.$this->maxUploadBytes()],
        ]);

        $disk = Storage::disk($this->disk());

        if (! $disk->providesTemporaryUploadUrls()) {
            return ApiResponse::conflict(
                'Direct uploads are not configured for this deployment.',
            );
        }

        $path = 'documents/pending/'.Str::uuid()->toString();
        $expiresAt = now()->addMinutes(
            (int) config('logistics.documents.upload_url_ttl_minutes', 15),
        );

        $signed = $disk->temporaryUploadUrl($path, $expiresAt, [
            'ContentType' => $data['mime_type'],
        ]);

        $token = Crypt::encryptString(json_encode([
            'path' => $path,
            'original_name' => $data['file_name'],
            'mime_type' => $data['mime_type'],
            'size_bytes' => (int) $data['size_bytes'],
            'user_id' => (int) $request->user()?->getAuthIdentifier(),
            'document_id' => $document?->getKey(),
            'expires_at' => $expiresAt->getTimestamp(),
        ], JSON_THROW_ON_ERROR));

        return ApiResponse::success([
            'upload_url' => $signed['url'],
            'upload_headers' => $this->uploadHeaders($signed['headers'] ?? [], $data['mime_type']),
            'upload_token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
        ], 'Upload URL created.');
    }

    /**
     * Browser-safe headers from a presigned PUT request.
     *
     * `Host` and `Content-Length` are set by the browser and cannot be assigned by
     * JavaScript. Everything else the signer returned has to be sent back exactly,
     * because a signed header with a different value is a signature mismatch.
     *
     * @param  array<string, array<int, string>|string>  $headers
     * @return array<string, string>
     */
    private function uploadHeaders(array $headers, string $mimeType): array
    {
        $safe = [];
        foreach ($headers as $name => $values) {
            $lower = strtolower($name);

            if ($lower === 'host' || $lower === 'content-length' || $lower === 'expect') {
                continue;
            }

            if ($lower === 'content-type') {
                $hasContentType = true;
            }

            $safe[$name] = implode(', ', (array) $values);
        }

        if (! $hasContentType) {
            $safe['Content-Type'] = $mimeType;
        }

        return $safe;
    }

    /**
     * Turn the request's upload half into a file, however it arrived.
     *
     * A multipart request carries the bytes; a presigned request carries only the
     * token, and the bytes are fetched from object storage into `/tmp` so the same
     * content validation runs either way.
     *
     * @return array{file: UploadedFile, pending: string|null}|null
     */
    private function resolveUpload(Request $request, ?Document $document): ?array
    {
        $hasFile = $request->hasFile('file');
        $hasToken = $request->filled('upload_token');

        if ($hasFile && $hasToken) {
            throw ValidationException::withMessages([
                'upload_token' => ['Send either a file or an upload token, not both.'],
            ]);
        }

        if ($hasFile) {
            $file = $request->file('file');

            if (! $file instanceof UploadedFile) {
                throw ValidationException::withMessages([
                    'file' => ['The file field must be a single uploaded file.'],
                ]);
            }

            return ['file' => $file, 'pending' => null];
        }

        if (! $hasToken) {
            return null;
        }

        $payload = $this->uploadPayload($request, $document);

        try {
            return [
                'file' => $this->downloadPendingUpload($payload),
                'pending' => $payload['path'],
            ];
        } catch (\Throwable $exception) {
            Storage::disk($this->disk())->delete($payload['path']);

            throw $exception;
        }
    }

    /**
     * Decrypt and validate a direct-upload token.
     *
     * @return array{path: string, original_name: string, mime_type: string, size_bytes: int, user_id: int, document_id: int|null, expires_at: int}
     */
    private function uploadPayload(Request $request, ?Document $document): array
    {
        try {
            $payload = json_decode(
                Crypt::decryptString((string) $request->input('upload_token')),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (DecryptException|JsonException) {
            throw ValidationException::withMessages([
                'upload_token' => ['The upload token is invalid. Upload the file again.'],
            ]);
        }

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'upload_token' => ['The upload token is invalid. Upload the file again.'],
            ]);
        }

        $documentId = $payload['document_id'] ?? null;

        if (
            ! is_string($payload['path'] ?? null)
            || preg_match('/^documents\/pending\/[0-9a-f-]{36}$/', $payload['path']) !== 1
            || ! is_string($payload['original_name'] ?? null)
            || ! is_string($payload['mime_type'] ?? null)
            || ! is_int($payload['size_bytes'] ?? null)
            || ! is_int($payload['user_id'] ?? null)
            || ! is_int($payload['expires_at'] ?? null)
            || (! is_int($documentId) && $documentId !== null)
        ) {
            throw ValidationException::withMessages([
                'upload_token' => ['The upload token is invalid. Upload the file again.'],
            ]);
        }

        if ($payload['expires_at'] < now()->timestamp) {
            throw ValidationException::withMessages([
                'upload_token' => ['The upload has expired. Upload the file again.'],
            ]);
        }

        if ($payload['user_id'] !== (int) $request->user()?->getAuthIdentifier()) {
            throw ValidationException::withMessages([
                'upload_token' => ['This upload was issued to a different account.'],
            ]);
        }

        if ($documentId !== $document?->getKey()) {
            throw ValidationException::withMessages([
                'upload_token' => ['This upload was issued for a different document.'],
            ]);
        }

        if ($payload['size_bytes'] < 1 || $payload['size_bytes'] > $this->maxUploadBytes()) {
            throw ValidationException::withMessages([
                'upload_token' => ['The uploaded file is too large.'],
            ]);
        }

        return $payload;
    }

    /**
     * Copy a pending object into a temporary file and validate its contents.
     */
    private function downloadPendingUpload(array $payload): UploadedFile
    {
        $disk = Storage::disk($this->disk());
        $path = $payload['path'];

        if (! $disk->exists($path)) {
            throw ValidationException::withMessages([
                'upload_token' => ['The uploaded file was not found. Upload it again.'],
            ]);
        }

        if ((int) $disk->size($path) !== $payload['size_bytes']) {
            $disk->delete($path);

            throw ValidationException::withMessages([
                'upload_token' => ['The uploaded file does not match the declared size. Upload it again.'],
            ]);
        }

        $temporary = tempnam(sys_get_temp_dir(), 'document-upload-');

        if ($temporary === false) {
            throw ValidationException::withMessages([
                'upload_token' => ['The uploaded file could not be processed. Please try again.'],
            ]);
        }

        $source = $disk->readStream($path);
        $target = fopen($temporary, 'wb');

        if ($source === false || $target === false) {
            if (is_resource($source)) {
                fclose($source);
            }

            if (is_resource($target)) {
                fclose($target);
            }

            @unlink($temporary);
            $disk->delete($path);

            throw ValidationException::withMessages([
                'upload_token' => ['The uploaded file could not be read. Upload it again.'],
            ]);
        }

        try {
            $copied = stream_copy_to_stream($source, $target);
        } finally {
            fclose($source);
            fclose($target);
        }

        if ($copied === false) {
            @unlink($temporary);
            $disk->delete($path);

            throw ValidationException::withMessages([
                'upload_token' => ['The uploaded file could not be read. Upload it again.'],
            ]);
        }

        $file = new UploadedFile(
            $temporary,
            $payload['original_name'],
            $payload['mime_type'],
            null,
            true,
        );

        try {
            Validator::make(['file' => $file], ['file' => $this->fileRules()])->validate();
        } catch (ValidationException $exception) {
            $disk->delete($path);
            @unlink($temporary);

            throw $exception;
        }

        return $file;
    }

    /**
     * Delete the staging object and temporary copy once the version is written.
     *
     * @param  array{file: UploadedFile, pending: string|null}|null  $upload
     */
    private function discardPendingUpload(?array $upload): void
    {
        if ($upload === null || $upload['pending'] === null) {
            return;
        }

        Storage::disk($this->disk())->delete($upload['pending']);
        @unlink((string) $upload['file']->getRealPath());
    }

    /**
     * @return array<int, File>
     */
    private function fileRules(): array
    {
        return [
            File::types(config('logistics.documents.allowed_mime_types'))
                ->max((int) config('logistics.documents.max_upload_kb')),
        ];
    }

    private function maxUploadBytes(): int
    {
        return max(1, (int) config('logistics.documents.max_upload_kb', 20480)) * 1024;
    }

    /**
     * The private disk documents live on.
     *
     * Read per call rather than cached in a property, because a long-lived worker
     * must see a config change made between two requests. Defaulting to `local`
     * rather than the app's default disk is the point: the default disk is
     * configurable per deployment, and a document register that followed it onto a
     * publicly served disk would publish every bill of lading the business holds.
     */
    private function disk(): string
    {
        return (string) config('logistics.documents.disk', 'local');
    }
}
