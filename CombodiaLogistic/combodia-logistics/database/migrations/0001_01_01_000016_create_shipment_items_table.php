<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('weight', 10, 3)->nullable();
            $table->decimal('volume', 10, 4)->nullable();
            $table->decimal('length', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->string('unit', 20)->nullable();
            $table->decimal('declared_value', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('shipment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
    }
};
