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
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('license_number', 50)->unique();
            $table->string('license_type')->comment('A, B, C, D, E');
            $table->date('license_expiry');
            $table->date('date_of_birth')->nullable();
            $table->string('national_id', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->string('status')->default('available')->comment('available, on_trip, off_duty, suspended');
            $table->date('hire_date')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
