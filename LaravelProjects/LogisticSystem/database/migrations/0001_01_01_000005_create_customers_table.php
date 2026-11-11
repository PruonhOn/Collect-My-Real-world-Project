<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('company')->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->text('address');
            $table->string('city');
            $table->string('province');
            $table->string('country')->default('Cambodia');
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('outstanding_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['phone', 'email']);
            $table->index('city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
