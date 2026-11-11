<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('student_id')->unique();
            $table->string('full_name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('date_of_birth');
            $table->tinyInteger('age')->nullable();
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('photo')->nullable();
            $table->enum('status', ['active', 'inactive', 'graduated', 'suspended'])->default('active');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['full_name', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
