<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('sender_name', 255)->nullable()->after('user_id');
            $table->string('sender_email', 255)->nullable()->after('sender_name');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['sender_name', 'sender_email']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
