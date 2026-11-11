<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('shipment_number')->unique();
            $table->enum('type', ['import', 'export', 'domestic', 'international']);
            $table->enum('status', [
                'pending', 'approved', 'pickup_scheduled', 'picked_up',
                'warehouse_received', 'in_transit', 'out_for_delivery',
                'delivered', 'failed', 'cancelled'
            ])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->string('sender_name');
            $table->string('sender_phone', 20);
            $table->text('sender_address');
            $table->string('sender_city', 100);
            $table->string('recipient_name');
            $table->string('recipient_phone', 20);
            $table->text('recipient_address');
            $table->string('recipient_city', 100);
            $table->decimal('total_weight', 10, 3)->default(0);
            $table->decimal('total_volume', 10, 4)->default(0);
            $table->integer('total_packages')->default(0);
            $table->decimal('total_freight_charge', 12, 2)->default(0);
            $table->decimal('total_insurance', 12, 2)->default(0);
            $table->decimal('total_tax', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->date('pickup_date')->nullable();
            $table->time('pickup_time')->nullable();
            $table->date('delivery_date')->nullable();
            $table->time('delivery_time')->nullable();
            $table->string('signed_by')->nullable();
            $table->string('signature_path', 500)->nullable();
            $table->boolean('is_paid')->default(false);
            $table->boolean('is_invoiced')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('warehouse_id');
            $table->index('user_id');
            $table->index('shipment_number');
            $table->index('status');
            $table->index('type');
            $table->index('priority');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
