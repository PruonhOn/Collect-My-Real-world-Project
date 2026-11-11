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
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('student_code', 50)->unique();
            $table->string('khmer_name', 191)->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('guardian_name', 191)->nullable();
            $table->string('guardian_phone', 40)->nullable();
            $table->string('address', 191)->nullable();
            $table->string('status', 20)->default('active');
            $table->date('enrolled_on')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
