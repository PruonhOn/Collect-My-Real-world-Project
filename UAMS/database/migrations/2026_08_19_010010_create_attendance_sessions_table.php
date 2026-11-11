<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_section_id')->constrained('class_sections')->cascadeOnDelete();
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->string('qr_token')->unique()->nullable();
            $table->integer('grace_period_minutes')->default(15);
            $table->enum('status', ['scheduled', 'active', 'closed'])->default('scheduled');
            $table->timestamps();

            $table->index(['class_section_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
