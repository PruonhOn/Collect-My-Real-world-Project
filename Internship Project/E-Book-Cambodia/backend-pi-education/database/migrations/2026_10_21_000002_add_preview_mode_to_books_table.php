<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Which label/semantics the free-page gate uses. `none` disables
            // the gate, `preview` is the original free preview, and `readtest`
            // is the trial-reading variant. The page count still lives in
            // `preview_pages` so existing data keeps working.
            $table->string('preview_mode')->default('none')->after('preview_pages');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('preview_mode');
        });
    }
};
