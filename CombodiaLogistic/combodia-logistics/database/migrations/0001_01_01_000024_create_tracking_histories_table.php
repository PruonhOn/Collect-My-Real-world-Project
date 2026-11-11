<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->foreignId('dispatch_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status', 50);
            $table->string('location_name', 255)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed', 6, 2)->nullable();
            $table->decimal('bearing', 5, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->enum('source', ['manual', 'gps_simulation', 'driver_app'])->default('manual');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('shipment_id');
            $table->index('dispatch_id');
            $table->index('status');
            $table->index('recorded_at');
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_histories');
    }
};
