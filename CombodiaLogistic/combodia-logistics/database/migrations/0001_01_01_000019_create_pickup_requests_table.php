<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('shipment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('request_number')->unique();
            $table->text('pickup_address');
            $table->string('pickup_city', 100)->nullable();
            $table->decimal('pickup_latitude', 10, 7)->nullable();
            $table->decimal('pickup_longitude', 10, 7)->nullable();
            $table->string('contact_name');
            $table->string('contact_phone', 20);
            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'assigned', 'picked_up', 'cancelled'])->default('pending');
            $table->foreignId('assigned_driver_id')->nullable()->constrained('drivers')->onDelete('set null');
            $table->foreignId('assigned_vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('shipment_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('scheduled_date');
            $table->index('assigned_driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_requests');
    }
};
