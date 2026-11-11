<?php

use App\Models\Author;
use App\Models\Book;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the author_id foreign key to books and backfill it from the legacy
     * books.author display string. This makes "one author has many books" a
     * real database relationship while keeping the author string column for
     * display and search compatibility.
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->foreignId('author_id')
                ->nullable()
                ->after('id')
                ->constrained('authors')
                ->nullOnDelete();

            $table->index('author_id');
        });

        $names = Book::query()
            ->whereNotNull('author')
            ->where('author', '!=', '')
            ->distinct()
            ->pluck('author');

        foreach ($names as $name) {
            $author = Author::firstOrCreate(['name' => $name]);
            Book::query()
                ->where('author', $name)
                ->update(['author_id' => $author->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
            $table->dropIndex(['author_id']);
            $table->dropColumn('author_id');
        });
    }
};
