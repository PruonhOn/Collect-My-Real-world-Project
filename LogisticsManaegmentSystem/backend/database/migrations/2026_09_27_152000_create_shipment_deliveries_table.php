<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The last leg: collecting the consignment and handing it over.
 *
 * One row per delivery attempt-leg, and a consignment may have several. A single
 * consignment is routinely delivered in parts - two drops from one truck at two
 * sites, a backorder that follows a week later, a failed attempt that is retried -
 * and each of those is a separate record with its own vehicle, its own slot and its
 * own outcome. Collapsing them onto one row per consignment would mean a retried
 * delivery overwriting the failure that explains why the retry happened, which is
 * the one fact the screen exists to produce.
 *
 * `reference` is globally unique, unlike the composite keys in the container and
 * customs tables, and the difference is the nature of the number: a delivery
 * reference is a document number *we* issue (`DLV-2026-000001` in
 * config/logistics.php), exactly like a shipment or a booking reference. It
 * identifies the record, so two deliveries cannot share one. The client's own
 * number - a carrier's job number, a consignment note - is not stored as an
 * identifier for the same reason a container number is not: it belongs to somebody
 * else and is reused.
 *
 * The `truck_id` and `driver_id` null on delete rather than cascading, matching the
 * master-data tables. A vehicle being retired from the fleet does not un-deliver
 * cargo, and a cascade here would take the record of a completed delivery with it.
 * The nullable result is a completed delivery whose vehicle is no longer on file,
 * which is a fact and not a gap.
 *
 * The delivery address is here and not read off the consignee because they are
 * routinely different places. A consignee's registered address is a head office; the
 * goods go to a site, a store, a port gate, or a bonded warehouse. The address the
 * vehicle actually went to is the thing being argued about when a delivery is
 * disputed, so it is recorded rather than inferred.
 *
 * No proof-of-delivery file is stored. Documents are a module of their own with their
 * own storage and their own authorisation, and `pod_reference` is the number the
 * paper carries so a document can be attached to it later without this table
 * becoming a second, unaudited place to put files.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_deliveries', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();

            $table->string('reference', 30)->unique();

            // Indexed rather than constrained: like every closed vocabulary in this
            // schema it is validated with `Rule::enum` at the boundary.
            $table->string('status', 30)->default('scheduled')->index();

            $table->foreignId('truck_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();

            /*
             * The slot agreed with the consignee, and the two facts that are only
             * known afterwards.
             *
             * All three nullable and all three timestamps rather than dates. The slot
             * is a time of day - "between 9 and 12" is what gets agreed, and a date
             * alone throws away the half of it that matters. `picked_up_at` and
             * `delivered_at` are events somebody signs or a driver taps, and both
             * are the sort of thing a delivery dispute is settled on, so they are
             * recorded to the minute.
             *
             * Not cross-validated against each other, and not against the shipment's
             * dates: a delivery can be booked in advance of an arrival, and a
             * comparison on a PATCH that sends one of the three would reject a
             * correction the operator is entitled to make.
             */
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            /*
             * Who to ring and where to go. Both nullable because the consignee
             * account file usually already holds them, and repeating it here is only
             * worth doing when this particular drop is different from the usual one.
             */
            $table->string('contact_name', 120)->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->text('delivery_address')->nullable();

            /*
             * The number on the paper somebody signed for, kept so a proof-of-delivery
             * document can be attached later and found again from the delivery itself.
             */
            $table->string('pod_reference', 60)->nullable();

            // Set with a `failed` status, and optional even then: a driver who could
            // not get an answer usually knows where they went and not why.
            $table->string('failure_reason', 255)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_deliveries');
    }
};
