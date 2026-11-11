<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('address');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('stop_order');
            $table->time('estimated_arrival_time')->nullable();
            $table->time('estimated_departure_time')->nullable();
            $table->timestamps();

            $table->index('route_id');
            $table->index('stop_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_stops');
    }
};
