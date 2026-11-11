<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payments\StripePaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(private StripePaymentGateway $gateway) {}

    public function handle(Request $request): JsonResponse
    {
        return $this->gateway->handleWebhook($request);
    }
}
