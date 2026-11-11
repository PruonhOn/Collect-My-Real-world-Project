<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('organization_departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('organization_positions')->nullOnDelete();
            $table->foreignId('reports_to_id')->nullable()->constrained('organization_members')->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('photo')->nullable();
            $table->string('type')->default('staff'); // staff, partner
            $table->text('bio')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_department_head')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_members');
    }
};
