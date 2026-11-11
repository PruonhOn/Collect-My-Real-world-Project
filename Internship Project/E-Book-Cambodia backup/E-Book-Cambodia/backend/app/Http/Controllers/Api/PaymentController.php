<?php

namespace App\Http\Controllers\Api;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    public function verify(Request $request, string $reference)
    {
        $payment = Payment::where('reference', $reference)
            ->whereHas('purchase', fn ($q) => $q->where('user_id', $request->user()->id))
            ->first();

        if (! $payment) {
            return $this->error('Payment not found', 404);
        }

        $gatewayService = new PaymentGatewayService();
        $result = $gatewayService->verifyPayment($reference, $payment->method);

        $payment->refresh();

        return $this->success([
            'payment' => [
                'reference' => $payment->reference,
                'method' => $payment->method,
                'status' => $result['status'],
                'provider_ref' => $result['provider_ref'] ?? $payment->provider_ref,
            ],
            'purchase_status' => $payment->purchase->status,
        ]);
    }
}
