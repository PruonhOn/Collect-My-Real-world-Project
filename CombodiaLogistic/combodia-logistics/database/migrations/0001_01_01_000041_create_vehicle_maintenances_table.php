<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('maintenance_type', ['routine', 'repair', 'inspection', 'accident', 'other']);
            $table->text('description');
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->string('performed_by')->nullable();
            $table->timestamps();

            $table->index('vehicle_id');
            $table->index('driver_id');
            $table->index('maintenance_type');
            $table->index('status');
            $table->index('scheduled_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenances');
    }
};
