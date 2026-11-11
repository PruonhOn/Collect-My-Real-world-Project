<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parties: customers, shippers and consignees.
 *
 * All three are companies with the same shape, and the permission catalog grants
 * them separate permissions (`customer.*`, `shipper.*`, `consignee.*`) without
 * that implying separate storage. One table plus a `type` discriminator keeps a
 * single address/contact definition to maintain, and the three API surfaces
 * filter on the column.
 *
 * `code` is unique per type rather than globally: the same short code, say
 * "ACME", may legitimately identify a customer and a shipper entry, and forcing
 * one global namespace would make that impossible to express.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table): void {
            $table->id();

            // customer | shipper | consignee - see App\Enums\PartyType.
            $table->string('type', 20)->index();
            $table->string('code', 50);
            $table->string('name');

            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('tax_number', 100)->nullable();

            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 30)->nullable();
            // ISO 3166-1 alpha-2, upper-cased by the model.
            $table->string('country_code', 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['type', 'code']);
            $table->index(['type', 'is_active']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
