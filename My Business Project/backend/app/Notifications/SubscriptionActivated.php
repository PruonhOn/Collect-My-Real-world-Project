<?php

namespace App\Notifications;

use App\Models\BusinessSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirms that a paid subscription became active for a business.
 */
class SubscriptionActivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BusinessSubscription $subscription) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $plan = $this->subscription->plan;
        $business = $this->subscription->business;

        $price = $this->subscription->billing_cycle === 'yearly'
            ? $plan->price_yearly
            : $plan->price_monthly;

        return (new MailMessage)
            ->subject("{$plan->name} is now active for {$business->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your {$plan->name} subscription for {$business->name} is now active.")
            ->line('Plan: '.$plan->name.' ('.$this->subscription->billing_cycle.')')
            ->line('Amount: '.$this->formatMoney($price, $plan->currency))
            ->line('Your Pro features are available immediately.')
            ->action('Open '.config('app.name'), config('app.frontend_url').'/business/dashboard');
    }

    protected function formatMoney(int $amount, string $currency): string
    {
        return $currency.' '.number_format($amount / 100, 2);
    }
}
