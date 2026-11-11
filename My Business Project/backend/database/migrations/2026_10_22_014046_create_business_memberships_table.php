<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnUpdate()->cascadeOnDelete();
            // Nullable: an invitation can be issued to an email address that has
            // not registered yet. It is linked to a user when they accept.
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('role')->default('staff'); // owner, admin, staff
            $table->enum('status', ['invited', 'active', 'revoked'])->default('invited');
            $table->string('invite_token')->nullable()->unique();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'user_id']);
            $table->unique(['business_id', 'email']);
            $table->index(['user_id', 'status']);
            $table->index(['business_id', 'status']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_memberships');
    }
};
