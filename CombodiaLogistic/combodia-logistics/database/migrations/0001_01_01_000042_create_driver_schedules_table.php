<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->foreignId('dispatch_id')->nullable()->constrained()->onDelete('set null');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->enum('status', ['scheduled', 'working', 'completed', 'absent', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('driver_id');
            $table->index('dispatch_id');
            $table->index('date');
            $table->index('status');
            $table->index(['driver_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_schedules');
    }
};
