<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['assigned', 'accepted', 'rejected', 'started', 'completed'])->default('assigned');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['dispatch_id', 'driver_id']);
            $table->index('dispatch_id');
            $table->index('driver_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_drivers');
    }
};
