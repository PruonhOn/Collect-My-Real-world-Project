<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['addition', 'deduction', 'correction']);
            $table->decimal('quantity_before', 12, 2);
            $table->decimal('quantity_after', 12, 2);
            $table->decimal('quantity_adjusted', 12, 2);
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->foreignId('adjusted_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('inventory_id');
            $table->index('warehouse_id');
            $table->index('type');
            $table->index('adjusted_by');
            $table->index('approved_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
