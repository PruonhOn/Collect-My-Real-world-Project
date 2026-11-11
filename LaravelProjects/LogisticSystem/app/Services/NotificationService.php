<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function send(string $type, string $title, string $message, int $notifiableId, string $notifiableType = User::class, ?string $icon = null, ?string $link = null): Notification
    {
        /** @var Notification */
        return $this->repository->create([
            'id' => (string) Str::uuid(),
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'icon' => $icon,
            'link' => $link,
            'is_read' => false,
            'notifiable_id' => $notifiableId,
            'notifiable_type' => $notifiableType,
        ]);
    }

    public function sendToUser(int $userId, string $type, string $title, string $message, ?string $icon = null, ?string $link = null): Notification
    {
        return $this->send($type, $title, $message, $userId, User::class, $icon, $link);
    }

    public function sendToRole(string $roleSlug, string $type, string $title, string $message, ?string $icon = null, ?string $link = null): Collection
    {
        $users = User::whereHas('roles', function ($query) use ($roleSlug) {
            $query->where('slug', $roleSlug);
        })->get();

        $notifications = collect();

        foreach ($users as $user) {
            $notifications->push(
                $this->send($type, $title, $message, $user->id, User::class, $icon, $link)
            );
        }

        return $notifications;
    }

    public function sendToAllUsers(string $type, string $title, string $message, ?string $icon = null, ?string $link = null): Collection
    {
        $users = User::where('is_active', true)->get();
        $notifications = collect();

        foreach ($users as $user) {
            $notifications->push(
                $this->send($type, $title, $message, $user->id, User::class, $icon, $link)
            );
        }

        return $notifications;
    }

    public function getAll(int $userId, array $filters = []): Collection
    {
        $query = Notification::where('notifiable_id', $userId)
            ->where('notifiable_type', User::class);

        if (isset($filters['is_read'])) {
            $query->where('is_read', $filters['is_read']);
        }

        return $query->latest()->get();
    }

    public function markAsRead(string $notificationId): ?Notification
    {
        /** @var Notification|null $notification */
        $notification = $this->repository->find($notificationId);

        if (!$notification) {
            return null;
        }

        $notification->markAsRead();

        return $notification->fresh();
    }

    public function markAllAsRead(int $userId): void
    {
        Notification::where('notifiable_id', $userId)
            ->where('notifiable_type', User::class)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function getUnreadCount(int $userId): int
    {
        return Notification::where('notifiable_id', $userId)
            ->where('notifiable_type', User::class)
            ->where('is_read', false)
            ->count();
    }

    public function getUserNotifications(int $userId, ?int $perPage = 15): LengthAwarePaginator
    {
        return Notification::where('notifiable_id', $userId)
            ->where('notifiable_type', User::class)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getRecentUnread(int $userId, int $limit = 5): Collection
    {
        return Notification::where('notifiable_id', $userId)
            ->where('notifiable_type', User::class)
            ->where('is_read', false)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
