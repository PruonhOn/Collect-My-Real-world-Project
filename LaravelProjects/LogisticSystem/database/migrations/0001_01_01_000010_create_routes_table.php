<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('origin_city');
            $table->string('destination_city');
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->text('description')->nullable();
            $table->json('waypoints')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['origin_city', 'destination_city']);
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number', 50)->unique();
            $table->date('scheduled_date');
            $table->dateTime('departed_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->string('status')->default('pending')->comment('pending, assigned, in_transit, delivered, failed, returned');
            $table->text('delivery_notes')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->string('recipient_signature')->nullable();
            $table->string('proof_photo')->nullable();
            $table->string('proof_document')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('route_id')->nullable()->constrained();
            $table->foreignId('driver_id')->nullable()->constrained();
            $table->foreignId('vehicle_id')->nullable()->constrained();
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'scheduled_date']);
            $table->index('driver_id');
        });

        Schema::create('delivery_stops', function (Blueprint $table) {
            $table->id();
            $table->integer('sequence_order');
            $table->string('type')->default('dropoff')->comment('pickup, dropoff');
            $table->string('address');
            $table->string('city');
            $table->string('contact_name');
            $table->string('contact_phone');
            $table->dateTime('scheduled_time')->nullable();
            $table->dateTime('arrived_at')->nullable();
            $table->dateTime('departed_at')->nullable();
            $table->string('status')->default('pending')->comment('pending, completed, skipped, failed');
            $table->text('notes')->nullable();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['delivery_id', 'sequence_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_stops');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('routes');
    }
};
