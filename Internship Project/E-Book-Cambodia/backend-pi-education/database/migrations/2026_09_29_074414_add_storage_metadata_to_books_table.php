<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('file_disk')->nullable()->after('file_path');
            $table->string('file_name')->nullable()->after('file_disk');
            $table->string('file_hash', 64)->nullable()->after('file_name');
            $table->string('cover_disk')->nullable()->after('cover_image');
        });

        DB::table('books')->whereNotNull('file_path')->update([
            'file_disk' => config('books.file_disk'),
        ]);
        DB::table('books')->whereNotNull('cover_image')->update([
            'cover_disk' => config('books.cover_disk'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['file_disk', 'file_name', 'file_hash', 'cover_disk']);
        });
    }
};
