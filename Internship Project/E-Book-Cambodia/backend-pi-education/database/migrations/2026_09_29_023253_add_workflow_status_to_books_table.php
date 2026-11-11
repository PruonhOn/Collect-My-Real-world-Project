<?php

use App\Models\Book;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `status` is the single source of truth for the editorial workflow:
     * draft -> pending -> approved|rejected -> published. The pre-existing
     * `is_published` boolean is kept in sync with it (see Book::transitionTo)
     * so every existing published-scope query keeps working unchanged.
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('status')->default(Book::STATUS_DRAFT)->after('is_coming_soon')->index();
            $table->text('review_note')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('review_note')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('reviewed_by');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            $table->timestamp('published_at')->nullable()->after('reviewed_at');
        });

        // Backfill: anything already live becomes `published`, everything else
        // stays a `draft` an author can edit and submit.
        DB::table('books')->where('is_published', true)->update(['status' => Book::STATUS_PUBLISHED]);
        DB::table('books')->where('is_published', false)->update(['status' => Book::STATUS_DRAFT]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            // The status index has to go explicitly: MySQL would drop it with
            // the column, but SQLite leaves it behind and then refuses to
            // drop the column it references.
            $table->dropIndex('books_status_index');
            $table->dropColumn([
                'status', 'review_note', 'reviewed_by',
                'submitted_at', 'reviewed_at', 'published_at',
            ]);
        });
    }
};
