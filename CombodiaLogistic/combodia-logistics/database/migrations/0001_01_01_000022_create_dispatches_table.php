<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->foreignId('route_id')->nullable()->constrained()->onDelete('set null');
            $table->string('dispatch_number')->unique();
            $table->enum('status', ['pending', 'assigned', 'loaded', 'in_transit', 'delivered', 'cancelled'])->default('pending');
            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('shipment_id');
            $table->index('route_id');
            $table->index('dispatch_number');
            $table->index('status');
            $table->index('scheduled_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatches');
    }
};
