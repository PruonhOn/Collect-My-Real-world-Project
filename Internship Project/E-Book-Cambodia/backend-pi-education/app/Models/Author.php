<?php

namespace App\Models;

use Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name', 'email', 'password', 'phone', 'status', 'photo', 'bio',
    'specialization', 'social_links', 'organization', 'school',
    'email_verified_at', 'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class Author extends Authenticatable
{
    /** @use HasFactory<AuthorFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'social_links' => 'array',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'author_id');
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Whether the book belongs to this author. Matches on the author_id
     * foreign key. The legacy author display-string fallback only applies to
     * books that have no author_id at all, so a display-name collision can
     * never let one author see or manage another author's linked book.
     */
    public function ownsBook(Book $book): bool
    {
        if ($book->author_id !== null) {
            return $book->author_id === $this->id;
        }

        return is_string($book->author) && $book->author !== '' && $book->author === $this->name;
    }

    /**
     * Base query for everything the author owns, covering both the foreign
     * key and any legacy string-matched books. String matching is restricted
     * to books with no author_id so shared display names cannot leak another
     * author's linked books into the author's workspace.
     */
    public function ownedBooksQuery()
    {
        return Book::query()
            ->where(function ($query) {
                $query->where('author_id', $this->id)
                    ->orWhere(function ($query) {
                        $query->whereNull('author_id')
                            ->where('author', $this->name);
                    });
            });
    }
}
