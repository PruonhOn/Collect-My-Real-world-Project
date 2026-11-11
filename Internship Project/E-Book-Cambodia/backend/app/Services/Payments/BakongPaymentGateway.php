<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Purchase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BakongPaymentGateway implements PaymentGatewayInterface
{
    protected string $baseUrl;
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected ?string $merchantId;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('payments.bakong.base_url', 'https://api-sandbox-bakong.nbc.gov.kh'), '/');
        $this->clientId = config('payments.bakong.client_id');
        $this->clientSecret = config('payments.bakong.client_secret');
        $this->merchantId = config('payments.bakong.merchant_id');
    }

    public function checkout(Purchase $purchase, array $payload = []): array
    {
        $reference = 'BAKONG-'.strtoupper(Str::random(12));

        $payment = Payment::create([
            'purchase_id' => $purchase->id,
            'reference' => $reference,
            'method' => 'bakong',
            'status' => 'pending',
            'amount' => $purchase->amount,
            'payload' => $payload,
        ]);

        $accessToken = $this->getAccessToken();

        if (! $accessToken) {
            return [
                'status' => 'pending',
                'provider_ref' => null,
                'checkout_url' => null,
                'qr_code' => null,
                'payment' => $payment,
            ];
        }

        try {
            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl.'/v1/bakong/create_qr', [
                    'merchant_id' => $this->merchantId,
                    'amount' => (string) $purchase->amount,
                    'reference_id' => $reference,
                    'callback_url' => config('payments.bakong.callback_url'),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $payment->update([
                    'provider_ref' => $data['psp_trans_id'] ?? $data['transactionId'] ?? null,
                    'payload' => array_merge($payload, $data),
                    'status' => 'pending',
                ]);

                return [
                    'status' => 'pending',
                    'provider_ref' => $payment->provider_ref,
                    'checkout_url' => $data['checkout_url'] ?? null,
                    'qr_code' => $data['qr_image'] ?? $data['qr_code'] ?? null,
                    'payment' => $payment,
                ];
            }
        } catch (\Exception $e) {
            report($e);
        }

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

        if ($payment->status === 'paid') {
            return ['status' => 'paid', 'provider_ref' => $payment->provider_ref];
        }

        $accessToken = $this->getAccessToken();

        if (! $accessToken || ! $payment->provider_ref) {
            return ['status' => $payment->status, 'provider_ref' => $payment->provider_ref];
        }

        try {
            $response = Http::withToken($accessToken)
                ->post($this->baseUrl.'/v1/bakong/check_transaction', [
                    'transaction_id' => $payment->provider_ref,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $status = strtolower($data['status'] ?? $data['response_code'] ?? $payment->status);

                if ($status === 'paid' || $status === '0000' || $status === 'success' || $status === 'completed') {
                    $payment->update(['status' => 'paid']);
                    $purchase = Purchase::find($payment->purchase_id);
                    if ($purchase) {
                        $purchase->update(['status' => 'paid']);
                    }

                    return ['status' => 'paid', 'provider_ref' => $payment->provider_ref];
                }

                if ($status === 'failed' || $status === 'cancelled' || $status === 'canceled') {
                    $payment->update(['status' => 'failed']);
                }
            }
        } catch (\Exception $e) {
            report($e);
        }

        return ['status' => $payment->status, 'provider_ref' => $payment->provider_ref];
    }

    public function isPaid(string $reference): bool
    {
        return $this->verify($reference)['status'] === 'paid';
    }

    public function getAccessToken(): ?string
    {
        $cacheKey = 'bakong_access_token';

        if ($cached = cache()->get($cacheKey)) {
            return $cached;
        }

        if (! $this->clientId || ! $this->clientSecret) {
            return null;
        }

        try {
            $response = Http::post($this->baseUrl.'/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'] ?? $data['token'] ?? null;

                if ($token) {
                    $expiresIn = $data['expires_in'] ?? 3600;
                    cache()->put($cacheKey, $token, $expiresIn - 60);

                    return $token;
                }
            }
        } catch (\Exception $e) {
            report($e);
        }

        return null;
    }
}
