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
            $table->string('tracking_number', 50)->unique();
            $table->string('shipment_type')->comment('local_delivery, import, export, container, air_freight, sea_freight');
            $table->string('status')->default('created')->comment('created, pending, warehouse_received, in_transit, out_for_delivery, delivered, cancelled');

            $table->string('origin_address');
            $table->string('origin_city');
            $table->string('origin_province');
            $table->string('destination_address');
            $table->string('destination_city');
            $table->string('destination_province');

            $table->string('shipper_name');
            $table->string('shipper_phone');
            $table->string('shipper_email')->nullable();
            $table->string('consignee_name');
            $table->string('consignee_phone');
            $table->string('consignee_email')->nullable();

            $table->decimal('weight', 10, 2)->comment('kg');
            $table->decimal('volume', 10, 2)->nullable()->comment('m3');
            $table->integer('pieces')->default(1);
            $table->text('description')->nullable();
            $table->decimal('declared_value', 15, 2)->default(0);
            $table->string('currency')->default('USD');

            $table->date('shipment_date');
            $table->date('estimated_delivery')->nullable();
            $table->date('actual_delivery')->nullable();
            $table->string('delivery_proof')->nullable();

            $table->boolean('is_express')->default(false);
            $table->boolean('is_insured')->default(false);
            $table->decimal('insurance_amount', 15, 2)->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->foreignId('origin_branch_id')->constrained('branches');
            $table->foreignId('destination_branch_id')->nullable()->constrained('branches');
            $table->foreignId('assigned_driver_id')->nullable()->constrained('drivers');
            $table->foreignId('assigned_vehicle_id')->nullable()->constrained('vehicles');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tracking_number', 'status']);
            $table->index(['shipment_date', 'status']);
            $table->index('customer_id');
        });

        Schema::create('shipment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['shipment_id', 'created_at']);
        });

        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('quantity');
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('volume', 10, 2)->nullable();
            $table->decimal('value', 15, 2)->nullable();
            $table->string('hs_code', 20)->nullable()->comment('Harmonized System code');
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('shipment_documents', function (Blueprint $table) {
            $table->id();
            $table->string('type')->comment('waybill, invoice, customs, proof_of_delivery, other');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->text('notes')->nullable();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_documents');
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipment_statuses');
        Schema::dropIfExists('shipments');
    }
};
