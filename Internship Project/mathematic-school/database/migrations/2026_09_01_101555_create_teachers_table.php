<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('specialization')->nullable();
            $table->string('qualification')->nullable();
            $table->string('photo')->nullable();
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active');
            $table->softDeletes();
            $table->timestamps();

            $table->index('full_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
