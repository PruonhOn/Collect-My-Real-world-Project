<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('plate_number', 20)->unique();
            $table->string('make');
            $table->string('model');
            $table->year('year')->nullable();
            $table->string('color')->nullable();
            $table->decimal('capacity_weight', 10, 2)->comment('kg');
            $table->decimal('capacity_volume', 10, 2)->comment('m3');
            $table->string('fuel_type')->nullable();
            $table->decimal('fuel_consumption', 8, 2)->nullable();
            $table->string('insurance_number')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->string('registration_number')->nullable();
            $table->date('registration_expiry')->nullable();
            $table->string('status')->default('available')->comment('available, in_use, maintenance, out_of_service');
            $table->string('ownership')->default('owned')->comment('owned, leased, rented');
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('vehicle_type_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'branch_id']);
        });

        Schema::create('vehicle_maintenances', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->string('maintenance_type')->comment('routine, repair, inspection, insurance');
            $table->text('description');
            $table->date('maintenance_date');
            $table->date('next_maintenance_date')->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('vendor')->nullable();
            $table->string('status')->default('scheduled')->comment('scheduled, in_progress, completed, cancelled');
            $table->text('notes')->nullable();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehicle_id', 'maintenance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenances');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('vehicle_types');
    }
};
