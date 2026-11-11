<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Purchase;
use Illuminate\Support\Str;

class MockPaymentService
{
    public function __construct(private PurchaseActivationService $activation) {}

    /**
     * Payment methods that require manual admin approval before the purchase
     * is activated (e.g. bank-transfer style QR payments).
     */
    public const MANUAL_APPROVAL_METHODS = ['acleda', 'wing'];

    /**
     * Process a mock payment. For demo purposes any request is "approved"
     * unless the reference or amount indicates otherwise.
     *
     * @return array{approved: bool, provider_ref?: string, message: string}
     */
    public function process(float $amount, array $payload = []): array
    {
        $approved = ($payload['mock_approve'] ?? true) === true;

        // Simulate a tiny delay so loading states on the frontend are visible.
        if (app()->runningUnitTests() === false) {
            usleep(300000);
        }

        if (! $approved) {
            return [
                'approved' => false,
                'message' => 'Payment was declined by the mock gateway.',
            ];
        }

        return [
            'approved' => true,
            'provider_ref' => 'MOCK-'.Str::upper(Str::random(10)),
            'message' => 'Payment approved.',
        ];
    }

    /**
     * Create a payment record and, on approval, mark the purchase paid+activated.
     *
     * For manual-approval methods (ACLEDA, Wing) the payment is received but the
     * purchase is left pending until an admin approves/activates it. For other
     * methods (ABA, Bakong) the purchase is activated immediately.
     */
    public function checkout(Purchase $purchase, array $payload = []): Payment
    {
        $result = $this->process(
            (float) $purchase->amount,
            $payload
        );

        $method = $payload['method'] ?? 'mock';
        $requiresApproval = in_array($method, self::MANUAL_APPROVAL_METHODS, true);

        $payment = Payment::create([
            'user_id' => $purchase->user_id,
            'purchase_id' => $purchase->id,
            'reference' => 'PAY-'.Str::upper(Str::random(16)),
            'method' => $method,
            'amount' => $purchase->amount,
            'status' => Payment::STATUS_PENDING,
            'provider_ref' => $result['provider_ref'] ?? null,
            'payload' => array_merge($payload, [
                'message' => $result['message'],
                'manual_approval' => $requiresApproval,
            ]),
        ]);

        if (! $result['approved']) {
            $payment->update(['status' => Payment::STATUS_FAILED]);
            $purchase->update(['status' => Purchase::STATUS_FAILED]);

            return $payment->fresh();
        }

        if ($requiresApproval) {
            // Payment received, but access is granted only after admin approval.
            return $payment->fresh();
        }

        $payment->update(['status' => Payment::STATUS_COMPLETED, 'paid_at' => now()]);
        $purchase->update([
            'status' => Purchase::STATUS_PAID,
            'activated_at' => now(),
        ]);

        $this->activation->activated($purchase->fresh());

        return $payment->fresh();
    }

    /**
     * Confirm that a pending manual-approval payment has been verified by an
     * admin, activating the linked purchase.
     */
    public function approve(Purchase $purchase): Payment
    {
        $payment = $purchase->payment;

        if (! $payment) {
            throw new \RuntimeException('No payment record for this purchase.');
        }

        $payment->update([
            'status' => Payment::STATUS_COMPLETED,
            'paid_at' => now(),
        ]);

        $purchase->update([
            'status' => Purchase::STATUS_PAID,
            'activated_at' => now(),
        ]);

        $this->activation->activated($purchase->fresh());

        return $payment->fresh();
    }
}
