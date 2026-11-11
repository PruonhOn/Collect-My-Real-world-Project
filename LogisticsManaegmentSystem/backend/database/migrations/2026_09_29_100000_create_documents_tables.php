<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The document register: what paperwork the business holds, and every copy of it.
 *
 * Two tables, and the second one is the interesting decision. A bill of lading
 * gets amended; a certificate gets reissued with a corrected expiry; somebody
 * scans the wrong page and uploads it again. Overwriting the file on each upload
 * would make the register a record of what was uploaded last and nothing else -
 * and the wrong copy is very often the copy somebody needs to prove they were
 * sent a correction. So `documents` holds the *description* and `document_versions`
 * holds the files, one row per upload, and the current file is the highest
 * `version_number`. `max_versions_per_document` in `config/logistics.php` is a
 * retention cap on that history rather than a reason to stop accepting an
 * amendment.
 *
 * Nothing here is reachable by a URL. There is no `public` disk in this table's
 * vocabulary at all: the file lives on the private disk named in config, the only
 * way to read one is `GET /documents/{id}/download`, and that route is guarded by
 * `document.download` - held separately from `document.view`, because being shown
 * that a bill of lading exists and being able to open it are different decisions
 * and a finance officer needs the first without the second.
 *
 * `documents` is soft-deleted and `document_versions` is not. The reverse would
 * be strange: a file on its own is not a document anybody can produce in court,
 * it is a byte string, and the archive flag belongs to the thing being archived.
 *
 * The cascading foreign key is a backstop, not the mechanism. It only fires when
 * the parent is hard-deleted, and `DocumentController::destroy()` soft-deletes the
 * parent to match every other delete endpoint in this API - so it removes the
 * version rows itself, in the same pass that removes the files. The constraint
 * exists for the case that controller cannot reach: a purge from a console, a
 * `forceDelete()` in a maintenance script, a cascading hard delete from something
 * added later. Without it, that path would leave file bytes on the private disk
 * that no surviving row points at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();

            /*
             * A closed vocabulary, validated with `Rule::enum` at the boundary and
             * indexed rather than constrained like every other one in this schema.
             */
            $table->string('document_type', 40)->index();

            /*
             * What a person would call this file, and the column the register is
             * searched and sorted by.
             *
             * Not derived from the file name: an inbound scan arrives called
             * `Scan_2026-03-21_0943.pdf` and the operator who filed it knows it as
             * "Maersk bill of lading for SHP-2026-0001". The name is theirs to
             * choose and it is the only thing on the row that reads in a register.
             */
            $table->string('title', 255);

            /*
             * The third party's own number on the document.
             *
             * Nullable and deliberately *not* unique, which is the opposite of
             * every other reference in this schema and for a good reason. A bill of
             * lading number is near-unique, but a commercial invoice number is
             * not: an export file of twenty consignments carries the same
             * shipper's invoice number on all twenty documents, and making it
             * unique would mean inventing a number per row to satisfy an index.
             * This is what the module searches on when somebody quotes a number off
             * a PDF they were sent.
             */
            $table->string('reference', 60)->nullable()->index();

            /*
             * The consignment this paperwork is about, and nullable.
             *
             * Null is the common case rather than an oversight: the fleet
             * insurance certificate, the ISO 9001 quality certificate and last
             * year's audited accounts belong to the business and not to a job.
             * `nullOnDelete` rather than cascade, because a consignment that is
             * removed from the system has had paperwork filed against it and
             * losing the bill of lading with it would destroy the evidence.
             */
            $table->foreignId('shipment_id')->nullable()->constrained('shipments')->nullOnDelete();

            /*
             * Two dates, both nullable and both dates rather than timestamps.
             *
             * A document is dated by the day it says on it - a certificate expires
             * at the end of a day, and a time on it would shift that by the hour
             * the request arrived. `issued_on` is null far more often than
             * `expires_on`; a packing list carries no date of its own and is
             * dated by the consignment.
             *
             * Indexed because "what expires next" is the worklist this table
             * exists to produce, and it is a plain range scan rather than a
             * correlated date calculation per row.
             */
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable()->index();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('document_versions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();

            /*
             * One, then two, then three. Allocated as the row above the current
             * maximum inside the transaction that writes it, and unique per
             * document as the hard backstop - two concurrent uploads racing to be
             * version 4 must not both succeed and silently overwrite each other.
             */
            $table->unsignedInteger('version_number');
            $table->unique(['document_id', 'version_number']);

            /*
             * The disk is stored per row rather than read from config at read
             * time, for the same reason an invoice stores its currency: a document
             * moved between disks, or a disk that failed and had to be pointed
             * somewhere else, must not make the existing rows unreadable. Config
             * decides where the *next* version goes; this column says where this
             * one went.
             */
            $table->string('disk', 32);

            /*
             * The path *within* the disk, never a URL. 512 because a
             * `documents/{id}/v{n}-{sanitised name}` path is short but the
             * sanitised name is the user's own filename and we keep as much of it
             * as will fit.
             */
            $table->string('path', 512);

            /*
             * What the file was called when it arrived, kept so a download is
             * served under the name the recipient expects. It is never used to
             * build a path - see `path` - because a filename is attacker-supplied
             * and `../../` in one must not be able to write outside the document's
             * own directory.
             */
            $table->string('original_name', 255);

            /*
             * Provenance of the upload, not validation of it.
             *
             * The mime type and size were already checked against
             * `config('logistics.documents.allowed_mime_types')` before the file was
             * written; these columns record what was actually stored, which is how
             * a register can answer "is that a 2 MB scan or a 40 MB one" without
             * opening anything. The checksum is the SHA-256 of the bytes, so an
             * upload of the same file twice is recognisable as such.
             *
             * Nullable because they are recorded from the client-declared upload
             * where the underlying stream does not expose them.
             */
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->char('checksum', 64)->nullable();

            /*
             * Who uploaded it.
             *
             * `nullOnDelete` because the register outlives the people in it: an
             * employee leaving the business must not make a document unattributable
             * or delete it. The name that goes with this id is presented when it is
             * still there and the row still renders when it is not.
             */
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
    }
};
