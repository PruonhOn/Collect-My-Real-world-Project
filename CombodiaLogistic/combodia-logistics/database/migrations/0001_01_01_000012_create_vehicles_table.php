<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_type_id')->constrained()->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('plate_number', 20)->unique();
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->integer('year')->nullable();
            $table->string('color', 50)->nullable();
            $table->decimal('capacity_weight', 10, 2)->nullable();
            $table->decimal('capacity_volume', 10, 2)->nullable();
            $table->enum('fuel_type', ['gasoline', 'diesel', 'electric', 'hybrid'])->default('diesel');
            $table->enum('status', ['available', 'in_use', 'maintenance', 'out_of_service'])->default('available');
            $table->date('insurance_expiry')->nullable();
            $table->date('registration_expiry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('branch_id');
            $table->index('vehicle_type_id');
            $table->index('status');
            $table->index('plate_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
