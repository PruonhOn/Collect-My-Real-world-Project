<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The boxes a consignment travels in.
 *
 * One row per container per consignment, so a sea shipment with three boxes has
 * three rows here and a road consignment with none has zero. The pair is
 * one-to-many from the shipment deliberately: a consignment is not required to be
 * containerised, and a single consignment can legitimately be a mix - two
 * forty-footers and a pallet of breakbulk on the same bill.
 *
 * `container_number` is unique *per consignment*, not globally, and that is the one
 * decision in this migration that could reasonably be got wrong. A container is a
 * physical asset with a working life of a decade, and it goes round again: box
 * MSCU1234567-9 carries consignment A today and consignment B in six months. A
 * global unique index would refuse the second one, and the failure would be absurd
 * - a 422 telling the operator the container does not exist when it is standing in
 * the yard in front of them. The number identifies the *asset*; the unique pair
 * identifies its appearance on a particular consignment, which is the thing this
 * table records.
 *
 * The consequence is deliberate: a row here is a pairing, not the box's history. If
 * an operator wants "every consignment this box has been on", that is a query
 * across consignment history and the activity log, not a relation on a row that
 * belongs to the current one.
 *
 * The foreign key cascades, matching `shipment_trackings` rather than the
 * `nullOnDelete` used by the master-data tables. The reasoning is the same: a
 * container row is a description of a consignment's equipment, and a hard-deleted
 * consignment has taken the consignment with it. A `containers` table that could
 * outlive its consignment would hold equipment rows naming shipments that no
 * longer exist, with no column to say so.
 *
 * Note that this cascade only ever fires on a hard delete. `shipments` soft
 * deletes, so an ordinary archive leaves the equipment in place - which is right,
 * because the whole point of archiving rather than deleting is that the consignment
 * can come back and its boxes with it.
 *
 * `iso_code` is nullable because not every box has one: out-of-gauge cargo and
 * breakbulk shipments have no ISO 6346 size/type code at all, and a required
 * column would mean either a placeholder value or a 422 on a legitimate consignment.
 * `type` is a free string for the same reason - "Out of gauge", "Open top",
 * "40ft high cube" are the carriers' own descriptions and the set keeps growing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_containers', function (Blueprint $table): void {
            $table->id();

            /*
             * The unique index is a composite pair and the order of the two columns
             * is deliberate: shipment first, because the index is read by every query
             * that loads a consignment's equipment, and `shipment_id` is on its own
             * the prefix of the index, so those queries are served by the leftmost
             * column alone.
             */
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('container_number', 20);

            /*
             * ISO 6346 is eleven characters, four of which are a check digit
             * computed from the owner code and serial. 20 is generous enough for the
             * carrier prefixes and internal suffixes that get appended in practice
             * without being loose enough to accept a paragraph.
             */
            $table->string('iso_code', 10)->nullable();
            $table->string('type', 50)->nullable();

            // Indexed rather than constrained: like every closed vocabulary in this
            // schema it is validated with `Rule::enum` at the boundary, and the index
            // exists so the status worklists below do not have to scan.
            $table->string('status', 30)->default('empty')->index();

            $table->string('seal_number', 50)->nullable();

            /*
             * All three weights in whole kilograms, and all nullable.
             *
             * They are the box's own figures, not the cargo's: tare is the empty
             * weight stencilled on the door, max gross is the rating, and gross is
             * what was weighed. They are recorded separately rather than derived
             * because the arithmetic does not close - a box loaded to its rating is
             * usually a small overload, and storing a computed net weight would
             * quietly round that away instead of showing it.
             *
             * Nullable because a box nobody has weighed is ordinary, and a missing
             * figure is not the same as zero.
             */
            $table->unsignedInteger('tare_weight_kg')->nullable();
            $table->unsignedInteger('max_gross_weight_kg')->nullable();
            $table->unsignedInteger('gross_weight_kg')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['shipment_id', 'container_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_containers');
    }
};
