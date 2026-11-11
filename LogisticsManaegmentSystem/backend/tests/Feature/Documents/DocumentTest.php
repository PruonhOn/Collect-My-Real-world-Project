<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Shipment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\MasterData\MasterDataTestCase;

/**
 * The document register: what paperwork the business holds, and how to get it back.
 *
 * CRUD is inherited and already covered everywhere else, so what is pinned down
 * here is the four things this module does that no other one does:
 *
 * - a document cannot exist without a file, and an update without a file keeps the
 *   copy it already has
 * - every upload is a new version and the old ones are retained, because an
 *   amendment is the ordinary case rather than an exception
 * - the file is behind its own permission, held separately from `document.view`
 * - expiry is three different questions, and the register must not answer them
 *   with one number
 */
class DocumentTest extends MasterDataTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Every test in this class is about files, and none of them should be
        // able to write into the real private disk by accident.
        Storage::fake('local');
    }

    /**
     * A payload with a valid upload attached.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function upload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Bill of lading, Rotterdam to Jebel Ali',
            'document_type' => DocumentType::BillOfLading->value,
            'file' => UploadedFile::fake()->create('bol.pdf', 12, 'application/pdf'),
        ], $overrides);
    }

    public function test_it_requires_a_session(): void
    {
        $this->postJson('/api/v1/documents', $this->upload())->assertUnauthorized();
    }

    /**
     * The split the catalogue exists for: a bill of lading can be listed without
     * being opened, so `document.view` and `document.download` are separate.
     */
    public function test_a_viewer_can_see_a_document_exists_but_cannot_open_it(): void
    {
        $document = Document::factory()->uploaded()->create();

        $this->actingAsRole('viewer')
            ->getJson('/api/v1/documents')
            ->assertOk()
            ->assertJsonPath('data.0.title', $document->title);

        // `viewer` holds `*.view` and nothing else, so this is the exact case the
        // separate download permission is for.
        $this->getJson('/api/v1/documents/'.$document->id.'/download')->assertForbidden();
    }

    public function test_a_clerk_can_upload_a_document_but_neither_replace_its_file_nor_delete_it(): void
    {
        $shipment = Shipment::factory()->create();

        $this->actingAsRole('operations-clerk')
            ->post('/api/v1/documents', $this->upload(['shipment_id' => $shipment->id]))
            ->assertCreated()
            ->assertJsonPath('data.document_type_label', 'Bill of lading')
            ->assertJsonPath('data.file.version', 1);

        $document = Document::query()->firstOrFail();

        // The clerk role holds document.view, .create and .download - and neither
        // of the two that change or remove an existing document.
        $this->put('/api/v1/documents/'.$document->id, ['title' => 'Renamed'])
            ->assertForbidden();

        $this->deleteJson('/api/v1/documents/'.$document->id)->assertForbidden();
    }

    public function test_a_document_cannot_be_described_without_its_file(): void
    {
        $this->actingAsRole('operations-clerk')
            ->postJson('/api/v1/documents', [
                'title' => 'Packing list',
                'document_type' => DocumentType::PackingList->value,
            ])
            // Named on the right field, rather than a generic failure: a document
            // with no file is not a document, and the person uploading one is the
            // only person who can say which part of the request was incomplete.
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    /**
     * The two asymmetric halves of the file rule in one test, because they are one
     * lifecycle: create demands a file, update treats its absence as "keep what is
     * there". A rule that required both would make correcting an expiry impossible,
     * because the operator would have to re-upload the file to change a date on it.
     */
    public function test_editing_the_details_keeps_the_copy_that_is_already_there(): void
    {
        $document = Document::factory()->uploaded()->create([
            'expires_on' => now()->toDateString(),
        ]);

        $this->actingAsRole('operations-manager')
            ->putJson('/api/v1/documents/'.$document->id, [
                'expires_on' => now()->addYear()->toDateString(),
            ])
            ->assertOk()
            ->assertJsonPath('data.file.version', 1)
            ->assertJsonPath('data.version_count', 1);

        $this->assertDatabaseCount('document_versions', 1);
    }

    /**
     * An amendment, which is the ordinary reason this table has a versions child.
     *
     * The old copy is kept rather than overwritten: the question the register
     * exists to answer is often "did we send them the correction, and which one",
     * and an amendment that destroyed its predecessor could not answer it.
     */
    public function test_a_replacement_file_becomes_a_new_version_and_keeps_the_old_one(): void
    {
        $document = Document::factory()->uploaded()->create();

        $this->actingAsRole('operations-manager')
            ->put('/api/v1/documents/'.$document->id, [
                'file' => UploadedFile::fake()->create('bol-amended.pdf', 14, 'application/pdf'),
            ])
            ->assertOk()
            ->assertJsonPath('data.file.name', 'bol-amended.pdf')
            ->assertJsonPath('data.file.version', 2)
            ->assertJsonPath('data.version_count', 2);

        $this->assertDatabaseCount('document_versions', 2);

        // Both files are still on the disk, which is the difference between a
        // history and a `current_file` column that has been overwritten.
        $paths = $document->versions()->pluck('path');
        $this->assertCount(2, $paths);

        foreach ($paths as $path) {
            Storage::disk('local')->assertExists($path);
        }
    }

    /**
     * Retention is a storage bound, not a reason to refuse an amendment.
     *
     * Capping the number of *stored* copies and capping the number of *uploads*
     * are different policies, and only the second of them leaves an operator
     * holding a corrected scan with no way to record it.
     */
    public function test_uploads_beyond_the_retention_cap_are_still_accepted_and_the_oldest_goes(): void
    {
        $document = Document::factory()->uploaded('original.pdf')->create();
        $originalPath = $document->currentVersion->path;

        config()->set('logistics.documents.max_versions_per_document', 2);

        // Two amendments, so this goes one past the cap rather than up to it.
        foreach (['second.pdf', 'third.pdf'] as $name) {
            $this->actingAsRole('operations-manager')
                ->put('/api/v1/documents/'.$document->id, [
                    'file' => UploadedFile::fake()->create($name, 10, 'application/pdf'),
                ])
                ->assertOk()
                // The upload succeeds even once the cap is reached; the prune is
                // what removes history, not a refusal.
                ->assertJsonPath('data.file.version', $name === 'second.pdf' ? 2 : 3);
        }

        // The two newest survive. Version numbers never restart or reuse, so "the
        // newest two" stays unambiguous after a prune.
        $this->assertSame(
            [2, 3],
            $document->versions()->orderBy('version_number')->pluck('version_number')->all(),
        );

        // And the copy that was dropped is dropped from the disk too, not just
        // from the table. A row deleted and a file left behind is exactly the
        // unreachable bill of lading this module is supposed to prevent.
        Storage::disk('local')->assertMissing($originalPath);
        $this->assertDatabaseMissing('document_versions', ['path' => $originalPath]);

        // The bytes that remain are the two most recent uploads, not the two
        // earliest. `versions()` is ordered ascending for reading, and a prune that
        // inherited that ordering would keep version 1 and delete the corrected
        // file - a register answering questions with a superseded copy.
        $this->assertSame(
            ['second.pdf', 'third.pdf'],
            $document->versions()->orderBy('version_number')->pluck('original_name')->all(),
        );
    }

    public function test_it_serves_the_current_file_under_the_name_it_arrived_with(): void
    {
        $document = Document::factory()->uploaded('maersk-bl.pdf')->create();

        $response = $this->actingAsRole('operations-clerk')
            ->get('/api/v1/documents/'.$document->id.'/download')
            ->assertOk();

        // Under the carrier's own name, not a path segment of ours: the recipient
        // saves this and it has to be recognisable in their own filing system.
        $response->assertDownload('maersk-bl.pdf');
    }

    public function test_the_list_offers_a_download_path_without_ever_offering_a_public_one(): void
    {
        $document = Document::factory()->uploaded()->create();

        $this->actingAsRole('finance-officer')
            ->getJson('/api/v1/documents')
            ->assertOk()
            // Root-relative and never absolute: the SPA resolves it against its own
            // base URL, and it points at a different host from the one serving the
            // SPA often enough that a URL built from APP_URL sends the file across
            // the network twice.
            ->assertJsonPath('data.0.download_url', '/api/v1/documents/'.$document->id.'/download');
    }

    /**
     * A file that has gone missing says so, rather than reporting a failure.
     *
     * A cleared disk, or a restore from a backup older than the upload, both leave
     * the row pointing at nothing. The document is still there and every other
     * field on it is still right, so this is a 409 rather than a 404 - and a 404 is
     * rendered with generic wording by design, which would have sent somebody
     * hunting for a document that was sitting right there on the screen.
     */
    public function test_a_missing_file_is_a_conflict_that_names_the_problem(): void
    {
        $document = Document::factory()->uploaded()->create();

        $document->currentVersion->update(['path' => 'documents/gone/missing.pdf']);

        $this->actingAsRole('operations-clerk')
            ->getJson('/api/v1/documents/'.$document->id.'/download')
            ->assertStatus(409)
            ->assertJsonPath('message', 'The file for this document is no longer on the storage disk. Upload it again.');
    }

    public function test_the_configured_type_allowlist_is_enforced(): void
    {
        $this->actingAsRole('operations-clerk')
            ->post('/api/v1/documents', $this->upload([
                'file' => UploadedFile::fake()->create('payload.exe', 4, 'application/x-msdownload'),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_the_configured_size_limit_is_enforced(): void
    {
        config()->set('logistics.documents.max_upload_kb', 1024);

        $this->actingAsRole('operations-clerk')
            ->post('/api/v1/documents', $this->upload([
                'file' => UploadedFile::fake()->create('huge.pdf', 2048, 'application/pdf'),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    /**
     * Paperwork outlives the job it was filed against.
     *
     * A consignment is removed from the system when it is closed out and the file
     * is never opened again; the bill of lading for it is the evidence that the
     * goods moved, and losing that on a soft delete would be losing the only reason
     * the consignment ever mattered.
     */
    public function test_a_document_outlives_the_consignment_it_was_filed_against(): void
    {
        $shipment = Shipment::factory()->create();

        $document = Document::factory()->create(['shipment_id' => $shipment->id]);

        // `forceDelete` rather than `delete` on purpose: a consignment is archived
        // long before it is purged, and the archive keeps the row - and the foreign
        // key with it. What this pins down is the purge, which is what the
        // `nullOnDelete` exists for.
        $shipment->forceDelete();

        $this->assertNotSoftDeleted($document);
        $this->assertNull($document->fresh()->shipment_id);

        $this->actingAsRole('operations-clerk')
            ->getJson('/api/v1/documents/'.$document->id)
            ->assertOk()
            ->assertJsonPath('data.shipment', null);
    }

    public function test_it_finds_a_document_by_the_consignment_it_belongs_to(): void
    {
        $shipment = Shipment::factory()->create(['reference' => 'OPWT-9001']);

        Document::factory()->uploaded()->create(['shipment_id' => $shipment->id]);
        Document::factory()->uploaded()->create(['title' => 'Something else entirely']);

        $this->actingAsRole('operations-clerk')
            ->getJson('/api/v1/documents?q=OPWT-9001')
            ->assertOk()
            // One row, not two: the reference has to match inside the same OR group
            // as the title, or a document matching on its consignment would be
            // dropped for not matching any searchable column of its own.
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.shipment.reference', 'OPWT-9001');
    }

    /**
     * Three questions, three answers, and none of them derived from another.
     *
     * This is the whole reason expiry is computed on the server: a register that
     * showed "expired" for a certificate with 200 days left, or left a lapsed one
     * off the worklist, would be acted on and be wrong.
     */
    public function test_expiry_is_expired_days_left_and_needs_attention_separately(): void
    {
        $expired = Document::factory()->expired()->create();
        $soon = Document::factory()->expiringSoon()->create();
        $valid = Document::factory()->validForLong()->create();
        $undated = Document::factory()->create(['expires_on' => null]);

        $this->actingAsRole('finance-officer')
            ->getJson('/api/v1/documents?sort=id&direction=asc')
            ->assertOk()
            // Created in id order above, so the array order is the assertion.
            ->assertJsonPath('data.0.is_expired', true)
            ->assertJsonPath('data.0.needs_attention', true)
            ->assertJsonPath('data.1.is_expired', false)
            // Inside the window but still valid: the distinction the worklist is for.
            ->assertJsonPath('data.1.needs_attention', true)
            ->assertJsonPath('data.2.needs_attention', false)
            // No expiry date is not an expiry and not an attention item. A packing
            // list does not lapse, and saying it needs attention would put every
            // piece of cargo paperwork on the same list as the certificates.
            ->assertJsonPath('data.3.is_expired', false)
            ->assertJsonPath('data.3.expires_in_days', null)
            ->assertJsonPath('data.3.needs_attention', false);

        $this->assertLessThan(0, $expired->expiresInDays());
        $this->assertGreaterThan(0, $soon->expiresInDays());
        // A long-dated certificate has days left and nothing else to report. The
        // pair of cases above and this one are what `needsAttention` is built on;
        // if `expiresInDays` conflated them the worklist would be either useless
        // or a wall.
        $this->assertGreaterThan(0, $valid->expiresInDays());
        $this->assertNull($undated->expiresInDays());
    }

    /**
     * `expiring` and `expired` are disjoint.
     *
     * Somebody asking what needs renewing and being handed yesterday's
     * certificates has to read every row twice to find the half they meant.
     */
    public function test_the_expiry_filters_do_not_overlap(): void
    {
        $expired = Document::factory()->expired()->create();
        $soon = Document::factory()->expiringSoon()->create();
        $valid = Document::factory()->validForLong()->create();

        $this->actingAsRole('finance-officer')
            ->getJson('/api/v1/documents?expiring=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $soon->id);

        $this->actingAsRole('finance-officer')
            ->getJson('/api/v1/documents?expired=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $expired->id);

        // And nothing about the default list changes, which is the point of them
        // being filters rather than a status.
        $this->actingAsRole('finance-officer')
            ->getJson('/api/v1/documents')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_an_expiry_before_the_issue_date_is_refused(): void
    {
        $document = Document::factory()->uploaded()->create([
            'issued_on' => now()->toDateString(),
            'expires_on' => now()->addYear()->toDateString(),
        ]);

        $this->actingAsRole('operations-manager')
            ->putJson('/api/v1/documents/'.$document->id, ['expires_on' => now()->subDay()->toDateString()])
            ->assertStatus(422)
            ->assertJsonValidationErrors('expires_on');
    }

    /**
     * Deleting the row has to take the bytes with it, and the rows naming them.
     *
     * The version row is the only record of where a private file is, so an archive
     * that left it behind would either strand bytes nobody can find, or - if the
     * rows survived too - produce a document that exists, opens, and 409s. Both
     * halves have to go, and they have to go together.
     */
    public function test_deleting_a_document_deletes_its_files_and_the_rows_naming_them(): void
    {
        $document = Document::factory()->amended()->create();
        $paths = $document->versions()->pluck('path');

        $this->assertCount(2, $paths);

        foreach ($paths as $path) {
            Storage::disk('local')->assertExists($path);
        }

        $this->actingAsRole('operations-manager')
            ->deleteJson('/api/v1/documents/'.$document->id)
            ->assertOk();

        // Soft-deleted like every other record in this API, so the parent stays as
        // the record that the document existed and when it went.
        $this->assertSoftDeleted($document);

        foreach ($paths as $path) {
            Storage::disk('local')->assertMissing($path);
            $this->assertDatabaseMissing('document_versions', ['path' => $path]);
        }

        // And no version rows survive against a parent that cannot be seen, which
        // would otherwise be a count of files that do not exist.
        $this->assertDatabaseCount('document_versions', 0);
    }

    /**
     * Archived is not gone, and the screen has to stop offering it.
     */
    public function test_an_archived_document_leaves_the_register(): void
    {
        $document = Document::factory()->uploaded()->create();

        $document->delete();

        $this->actingAsRole('operations-clerk')
            ->getJson('/api/v1/documents')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // And it is a 404 rather than a row that renders with no file.
        $this->getJson('/api/v1/documents/'.$document->id)->assertNotFound();
        $this->getJson('/api/v1/documents/'.$document->id.'/download')->assertNotFound();
    }
}
