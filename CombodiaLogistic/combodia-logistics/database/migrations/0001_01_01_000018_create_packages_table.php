<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('warehouse_shelf_id')->nullable()->constrained()->onDelete('set null');
            $table->string('package_number')->unique();
            $table->string('barcode')->unique()->nullable();
            $table->string('qr_code', 500)->nullable();
            $table->enum('type', ['box', 'envelope', 'pallet', 'drum', 'custom'])->default('box');
            $table->decimal('weight', 10, 3)->default(0);
            $table->decimal('volume', 10, 4)->default(0);
            $table->decimal('length', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->enum('status', [
                'pending', 'received', 'stored', 'loaded', 'in_transit',
                'out_for_delivery', 'delivered', 'returned', 'damaged', 'lost'
            ])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('shipment_id');
            $table->index('warehouse_id');
            $table->index('warehouse_shelf_id');
            $table->index('package_number');
            $table->index('barcode');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
