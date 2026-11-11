<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sharing_key_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'revoked'])->default('active');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['sharing_key_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_access');
    }
};
