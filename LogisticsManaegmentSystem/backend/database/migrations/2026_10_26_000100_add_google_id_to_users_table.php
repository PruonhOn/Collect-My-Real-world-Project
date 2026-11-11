<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Google subject identifier for an account created or linked through
     * Google sign-in.
     *
     * Nullable because the overwhelming majority of accounts are still created
     * with a password, and unique because one Google identity must map to one
     * local account. The email column remains the linking key: Google is only
     * allowed to attach to an account whose email is already verified.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn('google_id');
        });
    }
};
