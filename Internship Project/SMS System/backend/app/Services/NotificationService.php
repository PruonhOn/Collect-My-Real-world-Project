<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class NotificationService
{
    public function notify(User $user, string $type, string $title, ?string $body = null, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $user->getKey(),
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    /**
     * @param  Collection<int, User>|array<int, User>  $users
     */
    public function notifyMany(Collection|array $users, string $type, string $title, ?string $body = null, array $data = []): void
    {
        $rows = collect($users)->map(fn (User $user) => [
            'id' => (string) Str::uuid(),
            'user_id' => $user->getKey(),
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => json_encode($data),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        if (count($rows) > 0) {
            DB::table('notifications')->insert($rows);
        }
    }

    /**
     * Fan an announcement out to every user matching its audience. Audiences
     * are role names such as "student", "parent", "teacher" or "all".
     */
    public function announce(Announcement $announcement): int
    {
        $audience = $announcement->audience ?? ['all'];

        if (in_array('all', $audience, true)) {
            $users = User::query()->where('is_active', true)->get();
        } else {
            $users = User::role($audience)->where('is_active', true)->get();
        }

        $this->notifyMany(
            $users,
            'announcement',
            $announcement->title,
            $announcement->body,
            ['announcement_id' => $announcement->getKey()],
        );

        return count($users);
    }

    public function markRead(Notification $notification): void
    {
        $notification->read_at = now();
        $notification->save();
    }

    public function markAllRead(User $user): void
    {
        Notification::query()
            ->where('user_id', $user->getKey())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
