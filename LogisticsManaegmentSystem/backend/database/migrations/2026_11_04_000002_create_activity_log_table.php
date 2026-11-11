<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Activity log: an append-only record of what an authenticated actor did,
     * who they did it to, and when. Read by the Activity Logs screen; never
     * updated or deleted through the API.
     */
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->morphs('causer');
            $table->string('action');
            $table->morphs('subject');
            $table->json('properties')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
