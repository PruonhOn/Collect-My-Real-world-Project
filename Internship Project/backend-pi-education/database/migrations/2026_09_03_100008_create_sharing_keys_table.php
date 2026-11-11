<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sharing_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->unsignedInteger('max_uses')->default(3);
            $table->unsignedInteger('used_count')->default(0);
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sharing_keys');
    }
};
