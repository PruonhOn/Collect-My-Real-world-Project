<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->string('status', 50);
            $table->string('location', 255)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('shipment_id');
            $table->index('status');
            $table->index('changed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_status_histories');
    }
};
