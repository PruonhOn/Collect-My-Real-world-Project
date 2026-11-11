<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('publisher')->nullable()->after('author');
            $table->string('subject')->nullable()->after('publisher');
            $table->string('education_level')->nullable()->after('subject');
            $table->string('grade')->nullable()->after('education_level');
            $table->date('publication_date')->nullable()->after('grade');
            $table->boolean('is_vip_only')->default(false)->after('is_free');
            $table->unsignedBigInteger('view_count')->default(0)->after('pages');
            $table->unsignedBigInteger('purchase_count')->default(0)->after('view_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn([
                'publisher',
                'subject',
                'education_level',
                'grade',
                'publication_date',
                'is_vip_only',
                'view_count',
                'purchase_count',
            ]);
        });
    }
};
