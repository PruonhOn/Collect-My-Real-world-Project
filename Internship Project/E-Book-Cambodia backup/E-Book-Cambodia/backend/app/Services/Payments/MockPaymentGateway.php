<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Purchase;
use Illuminate\Support\Str;

class MockPaymentGateway implements PaymentGatewayInterface
{
    public function checkout(Purchase $purchase, array $payload = []): array
    {
        $reference = 'MOCK-'.strtoupper(Str::random(12));

        $payment = Payment::create([
            'purchase_id' => $purchase->id,
            'reference' => $reference,
            'method' => 'mock',
            'status' => 'pending',
            'amount' => $purchase->amount,
            'payload' => $payload,
        ]);

        return [
            'status' => 'pending',
            'provider_ref' => null,
            'checkout_url' => null,
            'qr_code' => null,
            'payment' => $payment,
        ];
    }

    public function verify(string $reference): array
    {
        $payment = Payment::where('reference', $reference)->first();

        if (! $payment) {
            return ['status' => 'failed', 'provider_ref' => null];
        }

        if (config('payments.mock.auto_approve', true)) {
            $payment->update(['status' => 'paid']);
            $this->markPurchasePaid($payment->purchase_id);
        }

        return ['status' => $payment->status, 'provider_ref' => $payment->provider_ref];
    }

    public function isPaid(string $reference): bool
    {
        $result = $this->verify($reference);

        return $result['status'] === 'paid';
    }

    protected function markPurchasePaid(int $purchaseId): void
    {
        $purchase = Purchase::find($purchaseId);
        if ($purchase) {
            $purchase->update(['status' => 'paid']);
        }
    }
}
