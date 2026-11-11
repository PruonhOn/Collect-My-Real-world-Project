<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('return_number')->unique();
            $table->enum('type', ['pickup', 'delivery_refused', 'damaged', 'wrong_item', 'other']);
            $table->enum('status', [
                'requested', 'approved', 'in_transit', 'received',
                'inspected', 'completed', 'rejected'
            ])->default('requested');
            $table->text('reason');
            $table->text('description')->nullable();
            $table->integer('total_items')->default(0);
            $table->decimal('total_refund_amount', 12, 2)->default(0);
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('shipment_id');
            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('return_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};
