<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->unique()->constrained('course_enrollments')->cascadeOnDelete();
            $table->string('letter_grade', 5)->nullable();
            $table->decimal('points', 4, 2)->default(0);
            $table->decimal('credit_hours', 6, 2)->default(0);
            $table->string('remarks', 191)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
