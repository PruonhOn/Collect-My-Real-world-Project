<?php

namespace App\Repositories;

use App\Models\Notification;

class NotificationRepository extends BaseRepository
{
    public function __construct(Notification $notification)
    {
        parent::__construct($notification);
    }

    public function model(): Notification
    {
        return new Notification();
    }
}
