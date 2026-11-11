<?php

namespace App\Notifications;

use App\Models\BusinessMembership;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invitation sent to a new team member of a business.
 */
class BusinessInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BusinessMembership $membership) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $membership = $this->membership;
        $business = $membership->business;

        $acceptUrl = config('app.frontend_url').'/invitations/'.$membership->invite_token;

        return (new MailMessage)
            ->subject('You have been invited to join '.$business->name)
            ->greeting('Hello,')
            ->line("You have been invited to join {$business->name} as a {$membership->role}.")
            ->action('Accept invitation', $acceptUrl)
            ->line('If you were not expecting this invitation you can ignore this email.');
    }
}
