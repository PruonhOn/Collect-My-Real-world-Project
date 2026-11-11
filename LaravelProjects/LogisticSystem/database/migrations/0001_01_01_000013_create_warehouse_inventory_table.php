<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('min_quantity', 10, 2)->default(0);
            $table->decimal('max_quantity', 10, 2)->nullable();
            $table->string('unit')->default('pcs')->comment('pcs, kg, box, pallet, container');
            $table->decimal('weight_per_unit', 10, 2)->nullable();
            $table->decimal('volume_per_unit', 10, 2)->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->string('barcode', 100)->nullable()->unique();
            $table->string('qr_code', 100)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['warehouse_id', 'sku']);
            $table->index('category');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->string('type')->comment('inbound, outbound, transfer, adjustment');
            $table->decimal('quantity', 10, 2);
            $table->decimal('quantity_before', 10, 2);
            $table->decimal('quantity_after', 10, 2);
            $table->text('reason')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('warehouse_locations');
            $table->foreignId('to_location_id')->nullable()->constrained('warehouse_locations');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['inventory_item_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_items');
    }
};
