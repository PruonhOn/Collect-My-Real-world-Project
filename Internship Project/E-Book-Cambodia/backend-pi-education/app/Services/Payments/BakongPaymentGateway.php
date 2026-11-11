<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Purchase;
use App\Services\PurchaseActivationService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;

class BakongPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private PurchaseActivationService $activation) {}

    public function checkout(Purchase $purchase, array $payload = []): array
    {
        $payment = Payment::create([
            'user_id' => $purchase->user_id,
            'purchase_id' => $purchase->id,
            'reference' => 'PAY-'.Str::upper(Str::random(16)),
            'method' => 'bakong',
            'amount' => $purchase->amount,
            'status' => Payment::STATUS_PENDING,
            'payload' => array_merge($payload, ['gateway' => 'bakong']),
        ]);

        $currency = strtoupper((string) config('payments.bakong.currency', 'USD'));
        $info = new IndividualInfo(
            (string) config('payments.bakong.account_id'),
            (string) config('payments.bakong.merchant_name'),
            (string) config('payments.bakong.merchant_city'),
        );
        $info->currency = $currency === 'KHR' ? KHQRData::CURRENCY_KHR : KHQRData::CURRENCY_USD;
        $info->amount = (float) $purchase->amount;
        $info->billNumber = $payment->reference;
        $info->purposeOfTransaction = 'PI Education purchase';

        $result = BakongKHQR::generateIndividual($info);
        $status = method_exists($result, 'getStatus') ? $result->getStatus() : ($result->status ?? []);
        $data = method_exists($result, 'getData') ? $result->getData() : ($result->data ?? []);
        if (($status['code'] ?? 1) !== 0 || ! isset($data['qr'], $data['md5'])) {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'payload' => array_merge($payment->payload ?? [], ['error' => $status]),
            ]);
            throw new \RuntimeException($status['message'] ?? 'Unable to generate Bakong KHQR.');
        }
        $payment->update([
            'provider_ref' => $data['md5'],
            'payload' => array_merge($payment->payload ?? [], [
                'qr' => $data['qr'],
                'md5' => $data['md5'],
                'currency' => $currency,
                'merchant_name' => config('payments.bakong.merchant_name'),
                'merchant_city' => config('payments.bakong.merchant_city'),
            ]),
        ]);

        return [
            'status' => Payment::STATUS_PENDING,
            'provider_ref' => $data['md5'],
            'checkout_url' => null,
            'payment' => $payment->fresh(),
        ];
    }

    public function check(Payment $payment): array
    {
        abort_unless($payment->method === 'bakong', 422, 'This payment is not a Bakong payment.');
        abort_unless($payment->provider_ref, 422, 'This Bakong payment has no transaction reference.');

        $token = (string) config('payments.bakong.token');
        if (trim($token) === '') {
            return $this->failure('Bakong is not configured. Set BAKONG_API_TOKEN in your environment.', 503);
        }

        if (BakongKHQR::isExpiredToken($token)) {
            return $this->failure('The Bakong API token is missing or expired. Renew it from the Bakong open API portal.', 503);
        }

        $url = rtrim((string) config('payments.bakong.base_url'), '/').'/v1/check_transaction_by_md5';

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout((int) config('payments.bakong.timeout', 10))
                ->post($url, ['md5' => $payment->provider_ref]);
        } catch (ConnectionException $exception) {
            report($exception);

            return $this->failure('The Bakong payment service is unreachable. Please try again.', 502);
        }

        $body = $response->json();
        if (! is_array($body)) {
            return $this->failure('The Bakong payment service returned an invalid response.', 502);
        }

        $data = $body['data'] ?? null;
        $isPaid = ($body['responseCode'] ?? 1) === 0
            && is_array($data)
            && strtoupper((string) ($data['currency'] ?? '')) === strtoupper((string) config('payments.bakong.currency', 'USD'))
            && abs((float) ($data['amount'] ?? 0) - (float) $payment->amount) < 0.001;

        if ($isPaid && $payment->status === Payment::STATUS_PENDING) {
            $payment->update([
                'status' => Payment::STATUS_COMPLETED,
                'paid_at' => now(),
                'payload' => array_merge($payment->payload ?? [], ['transaction' => $data]),
            ]);

            $purchase = $payment->purchase;
            if ($purchase !== null) {
                $purchase->update([
                    'status' => Purchase::STATUS_PAID,
                    'activated_at' => now(),
                ]);
                $this->activation->activated($purchase->fresh());
            }
        }

        return [
            'success' => true,
            'paid' => $isPaid,
            'status' => $payment->fresh()->status,
            'payment' => $payment->fresh(),
            'bakong' => $body,
        ];
    }

    /**
     * @return array{success: bool, message: string, http_status: int}
     */
    private function failure(string $message, int $httpStatus): array
    {
        return [
            'success' => false,
            'message' => $message,
            'http_status' => $httpStatus,
        ];
    }
}
