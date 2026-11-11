<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Warehouses and factories: the physical sites cargo passes through or
 * originates from.
 *
 * They share a migration because they are the same kind of record - a named
 * site with an address, a country and an operational status - and are separated
 * by a `kind` discriminator rather than by two tables. Splitting them later is
 * cheap; merging two half-populated tables afterwards is not.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table): void {
            $table->id();

            // warehouse | factory - see App\Enums\SiteKind.
            $table->string('kind', 20)->index();
            $table->string('code', 50);
            $table->string('name');

            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();

            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 30)->nullable();
            $table->string('country_code', 2)->nullable();

            // Warehouses only: general, bonded, cold_store, transhipment.
            $table->string('site_type', 50)->nullable();
            $table->unsignedInteger('capacity_pallets')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kind', 'code']);
            $table->index(['kind', 'is_active']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
