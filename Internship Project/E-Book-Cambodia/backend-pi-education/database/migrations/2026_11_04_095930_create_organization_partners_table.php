<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained('organization_members')->cascadeOnDelete();
            $table->string('organization_name')->nullable();
            $table->string('role')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_partners');
    }
};
