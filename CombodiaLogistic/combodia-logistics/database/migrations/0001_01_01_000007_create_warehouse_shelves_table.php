<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_shelves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_zone_id')->constrained()->onDelete('cascade');
            $table->string('code', 50);
            $table->string('name');
            $table->decimal('max_weight', 10, 2)->nullable();
            $table->decimal('current_weight', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('warehouse_zone_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_shelves');
    }
};
