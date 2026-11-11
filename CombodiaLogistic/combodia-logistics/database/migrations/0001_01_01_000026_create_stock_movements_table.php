<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->string('reference_type', 100);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->enum('type', ['in', 'out', 'transfer', 'adjustment', 'return']);
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->string('from_location', 255)->nullable();
            $table->string('to_location', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('moved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('inventory_id');
            $table->index('warehouse_id');
            $table->index('reference_type');
            $table->index('reference_id');
            $table->index('type');
            $table->index('moved_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
