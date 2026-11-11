<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Purchase;
use App\Services\PurchaseActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

/**
 * Real payment gateway powered by Stripe Checkout (hosted payment page).
 *
 * The purchase + a pending payment record are created up-front; the user is
 * redirected to Stripe. When the hosted session is completed, Stripe calls our
 * webhook, which marks the payment completed and activates the purchase.
 */
class StripePaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private PurchaseActivationService $activation) {}

    private const ZERO_DECIMAL_CURRENCIES = ['jpy', 'krw', 'vnd', 'clp', 'pyg', 'rwf'];

    public function checkout(Purchase $purchase, array $payload = []): array
    {
        Stripe::setApiKey(config('payments.stripe.secret_key'));

        $currency = config('payments.stripe.currency', 'usd');
        $amountCents = (int) round(
            (float) $purchase->amount * (in_array($currency, self::ZERO_DECIMAL_CURRENCIES, true) ? 1 : 100)
        );

        $payment = Payment::create([
            'user_id' => $purchase->user_id,
            'purchase_id' => $purchase->id,
            'reference' => 'PAY-'.Str::upper(Str::random(16)),
            'method' => 'stripe',
            'amount' => $purchase->amount,
            'status' => Payment::STATUS_PENDING,
            'payload' => array_merge($payload, ['gateway' => 'stripe']),
        ]);

        $itemName = $payload['item_name'] ?? 'Digital Library purchase';

        $session = Session::create([
            'mode' => 'payment',
            'customer_email' => $purchase->user->email,
            'client_reference_id' => (string) $purchase->id,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $amountCents,
                    'product_data' => ['name' => Str::limit($itemName, 100)],
                ],
            ]],
            'success_url' => rtrim(config('frontend.url'), '/').'/payments/status?result=paid&purchase='.$purchase->id,
            'cancel_url' => rtrim(config('frontend.url'), '/').'/payments/status?result=cancelled',
            'metadata' => ['purchase_id' => (string) $purchase->id],
        ]);

        $payment->update(['provider_ref' => $session->id]);

        return [
            'status' => Payment::STATUS_PENDING,
            'provider_ref' => $session->id,
            'checkout_url' => $session->url,
            'payment' => $payment->fresh(),
        ];
    }

    /**
     * Handle an incoming Stripe webhook. The signature must be valid; when no
     * webhook secret is configured the endpoint refuses to process any payload.
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        $secret = config('payments.stripe.webhook_secret');
        if (! $secret) {
            return response()->json(['error' => 'Webhook signature secret is not configured.'], 400);
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                (string) $request->header('Stripe-Signature'),
                $secret
            );
        } catch (SignatureVerificationException) {
            return response()->json(['error' => 'Invalid signature.'], 400);
        } catch (\UnexpectedValueException) {
            return response()->json(['error' => 'Invalid payload.'], 400);
        }

        if (($event->type ?? '') === 'checkout.session.completed') {
            $this->completeSession($event->data->object);
        }

        return response()->json(['received' => true]);
    }

    private function completeSession(object $session): void
    {
        $payment = Payment::where('provider_ref', $session->id)
            ->where('status', Payment::STATUS_PENDING)
            ->first();

        if ($payment) {
            $this->complete($payment);
        }
    }

    private function complete(Payment $payment): void
    {
        $payment->update([
            'status' => Payment::STATUS_COMPLETED,
            'paid_at' => now(),
        ]);

        $payment->purchase->update([
            'status' => Purchase::STATUS_PAID,
            'activated_at' => now(),
        ]);

        $this->activation->activated($payment->purchase->fresh());
    }
}
