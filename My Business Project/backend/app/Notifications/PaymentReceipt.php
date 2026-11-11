<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Receipt for a settled platform subscription payment.
 */
class PaymentReceipt extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payment = $this->payment;
        $invoice = $payment->invoice;

        $message = (new MailMessage)
            ->subject('Payment receipt '.$payment->payment_number)
            ->greeting("Hello {$notifiable->name},")
            ->line('We received your payment. Thank you.')
            ->line('Payment reference: '.$payment->payment_number)
            ->line('Amount: '.$this->formatMoney((int) $payment->amount, (string) $payment->currency));

        if ($invoice) {
            $message->line('Invoice: '.$invoice->invoice_number);
        }

        return $message
            ->action('View billing', config('app.frontend_url').'/business/billing');
    }

    protected function formatMoney(int $amount, string $currency): string
    {
        return $currency.' '.number_format($amount / 100, 2);
    }
}
