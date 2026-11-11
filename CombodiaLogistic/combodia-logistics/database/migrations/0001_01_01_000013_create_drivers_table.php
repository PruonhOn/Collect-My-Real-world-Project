<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_id')->nullable()->constrained()->onDelete('set null');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20);
            $table->string('alternate_phone', 20)->nullable();
            $table->string('license_number', 50)->unique();
            $table->date('license_expiry');
            $table->date('date_of_birth')->nullable();
            $table->enum('status', ['available', 'busy', 'off_duty', 'suspended', 'terminated'])->default('available');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('branch_id');
            $table->index('user_id');
            $table->index('vehicle_id');
            $table->index('status');
            $table->index('license_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
