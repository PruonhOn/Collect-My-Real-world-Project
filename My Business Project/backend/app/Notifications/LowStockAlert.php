<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Warns a business owner that a tracked product fell below its threshold.
 */
class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->product;

        return (new MailMessage)
            ->subject('Low stock: '.$product->name)
            ->greeting("Hello {$notifiable->name},")
            ->line("The product {$product->name} is running low.")
            ->line('Remaining stock: '.(int) $product->stock_quantity)
            ->line('Low stock threshold: '.(int) $product->low_stock_threshold)
            ->action('Manage inventory', config('app.frontend_url').'/business/products');
    }
}
