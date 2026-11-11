<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->change();
            $table->foreignId('program_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable(false)->change();
            $table->foreignId('program_id')->nullable(false)->change();
        });
    }
};
