<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'phone', 'avatar', 'role', 'vip_until', 'vip_plan', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements CanResetPasswordContract, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_READER = 'reader';

    public const ROLE_ADMIN = 'admin';

    protected $appends = ['is_vip'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'vip_until' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isVip(): bool
    {
        return $this->vip_until !== null && $this->vip_until->isFuture();
    }

    public function getIsVipAttribute(): bool
    {
        return $this->isVip();
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function readingProgress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function sharingKeys(): HasMany
    {
        return $this->hasMany(SharingKey::class, 'owner_id');
    }

    public function sharedAccess(): HasMany
    {
        return $this->hasMany(SharedAccess::class);
    }

    public function ownedBooks(): HasMany
    {
        return $this->hasMany(Purchase::class)->where('status', 'paid');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Send a password reset notification to the user. The notification links
     * to the React SPA reset page instead of a Laravel web view.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
