<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20);
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->integer('payment_terms')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
