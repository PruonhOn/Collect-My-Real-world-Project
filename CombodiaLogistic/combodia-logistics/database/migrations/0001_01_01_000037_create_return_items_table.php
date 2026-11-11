<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained()->onDelete('cascade');
            $table->foreignId('shipment_item_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('package_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('quantity')->default(1);
            $table->enum('condition', ['good', 'damaged', 'defective'])->default('good');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('return_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
    }
};
