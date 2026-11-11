<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\VipPlan;
use App\Services\PaymentGatewayService;
use App\Services\VipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    private const PAYMENT_METHODS = ['aba', 'acleda', 'wing', 'bakong', 'stripe', 'mock'];

    public function __construct(private PaymentGatewayService $paymentService) {}

    public function purchase(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->is_published, 404);

        if ($book->is_coming_soon) {
            return response()->json(['message' => 'This book is not yet available for purchase.'], 422);
        }

        if ($book->is_free) {
            return response()->json(['message' => 'This book is free. No payment required.'], 422);
        }

        if ($book->is_vip_only) {
            return response()->json([
                'message' => 'This book is available to VIP members only. Become a VIP to read it.',
                'vip_required' => true,
            ], 422);
        }

        $user = $request->user();
        $method = $request->input('method', 'mock');
        abort_unless(in_array($method, self::PAYMENT_METHODS, true), 422, 'Invalid payment method.');

        $existing = Purchase::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', Purchase::STATUS_PAID)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You already own this book.'], 422);
        }

        $purchase = Purchase::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => $book->price,
            'status' => Purchase::STATUS_PENDING,
        ]);

        try {
            $result = $this->paymentService->checkout($purchase, [
                'method' => $method,
                'book_slug' => $book->slug,
                'item_name' => $book->title,
                'mock_approve' => $request->input('mock_approve', true),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => $exception->getMessage() ?: 'Unable to start the payment.'], 422);
        }

        return $this->checkoutResponse($result);
    }

    public function purchaseCourse(Request $request, Course $course): JsonResponse
    {
        abort_unless($course->is_published, 404);

        $user = $request->user();
        $method = $request->input('method', 'mock');
        abort_unless(in_array($method, self::PAYMENT_METHODS, true), 422, 'Invalid payment method.');

        $existing = Purchase::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', Purchase::STATUS_PAID)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You already own this course.'], 422);
        }

        $purchase = Purchase::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $course->price,
            'status' => Purchase::STATUS_PENDING,
        ]);

        $result = $this->paymentService->checkout($purchase, [
            'method' => $method,
            'course_slug' => $course->slug,
            'item_name' => $course->title,
            'mock_approve' => $request->input('mock_approve', true),
        ]);

        return $this->checkoutResponse($result);
    }

    public function purchaseVip(Request $request, VipPlan $vipPlan): JsonResponse
    {
        abort_unless($vipPlan->is_active, 404);
        abort_if($vipPlan->is_test && ! VipService::testModeEnabled(), 403, 'Test VIP cards are disabled outside test mode.');

        $user = $request->user();
        $method = $request->input('method', 'mock');
        abort_unless(in_array($method, self::PAYMENT_METHODS, true), 422, 'Invalid payment method.');

        $itemKey = VipService::ITEM_KEY_PREFIX.$vipPlan->slug;

        $purchase = Purchase::create([
            'user_id' => $user->id,
            'external_item_key' => $itemKey,
            'amount' => $vipPlan->price,
            'status' => Purchase::STATUS_PENDING,
        ]);

        $result = $this->paymentService->checkout($purchase, [
            'method' => $method,
            'item_name' => $vipPlan->name.' (VIP)',
            'item_key' => $itemKey,
            'mock_approve' => $request->input('mock_approve', true),
        ]);

        return $this->checkoutResponse($result);
    }

    public function purchaseDigital(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'item_name' => ['nullable', 'string', 'max:255'],
            'item_key' => ['required', 'string', 'max:255'],
        ]);

        $itemName = $validated['item_name'] ?? 'Digital e-book';
        $alreadyOwned = Purchase::where('user_id', $request->user()->id)
            ->where('status', Purchase::STATUS_PAID)
            ->where(function ($query) use ($validated, $itemName) {
                $query->where('external_item_key', $validated['item_key'])
                    ->orWhereHas('payment', fn ($payment) => $payment->whereJsonContains('payload->item_name', $itemName));
            })
            ->exists();

        if ($alreadyOwned) {
            return response()->json(['message' => 'You already own this e-book.'], 409);
        }

        $accountId = (string) config('payments.bakong.account_id');
        if (! str_contains($accountId, '@')) {
            return response()->json([
                'message' => 'Bakong account ID is invalid. Use the exact account@bank ID from your bank, not only a phone number.',
            ], 422);
        }

        $purchase = Purchase::create([
            'user_id' => $request->user()->id,
            'external_item_key' => $validated['item_key'],
            'amount' => $validated['amount'],
            'status' => Purchase::STATUS_PENDING,
        ]);

        try {
            $result = $this->paymentService->checkout($purchase, [
                'method' => 'bakong',
                'item_name' => $itemName,
                'item_key' => $validated['item_key'],
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Unable to generate Bakong QR. Check the Bakong configuration (token and account ID).'], 422);
        }

        return $this->checkoutResponse($result);
    }

    /**
     * @param  array{status: string, provider_ref: string|null, checkout_url: string|null, payment: Payment}  $result
     */
    private function checkoutResponse(array $result): JsonResponse
    {
        $payment = $result['payment'];
        $payload = $payment->payload ?? [];

        if ($payment->method === 'bakong') {
            return response()->json([
                'message' => 'Scan the Bakong KHQR to complete payment.',
                'payment' => $payment,
                'activated' => false,
                'status' => $payment->status,
                'bakong' => [
                    'qr' => $payload['qr'] ?? null,
                    'md5' => $payload['md5'] ?? $payment->provider_ref,
                    'currency' => $payload['currency'] ?? config('payments.bakong.currency'),
                    'amount' => $payment->amount,
                    'merchant_name' => $payload['merchant_name'] ?? config('payments.bakong.merchant_name'),
                    'merchant_city' => $payload['merchant_city'] ?? config('payments.bakong.merchant_city'),
                    'check_url' => url('/api/v1/payments/'.$payment->id.'/bakong/check'),
                ],
            ], 202);
        }

        if ($result['checkout_url']) {
            return response()->json([
                'message' => 'Redirecting to payment gateway.',
                'checkout_url' => $result['checkout_url'],
                'payment' => $payment,
                'activated' => false,
                'status' => $payment->status,
            ], 202);
        }

        return response()->json([
            'message' => $payment->isCompleted()
                ? 'Payment successful. Access activated.'
                : ($payment->status === Payment::STATUS_PENDING
                    ? 'Payment received. Awaiting admin approval.'
                    : 'Payment failed.'),
            'payment' => $payment,
            'activated' => $payment->isCompleted(),
            'status' => $payment->status,
        ], $payment->isCompleted() ? 200 : ($payment->status === Payment::STATUS_PENDING ? 202 : 402));
    }

    public function index(Request $request): JsonResponse
    {
        $payments = Payment::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return response()->json($payments);
    }

    public function ownedItems(Request $request): JsonResponse
    {
        $purchases = Purchase::with('payment')
            ->where('user_id', $request->user()->id)
            ->where('status', Purchase::STATUS_PAID)
            ->get();

        // Include legacy purchases whose item key was stored only in the
        // payment payload before external_item_key was introduced.
        $itemKeys = $purchases->flatMap(function (Purchase $purchase) {
            $keys = $purchase->external_item_key
                ? explode(',', $purchase->external_item_key)
                : [];
            $legacyName = data_get($purchase->payment?->payload, 'item_name');
            if (is_string($legacyName) && trim($legacyName) !== '') {
                $keys[] = $legacyName;
            }

            return $keys;
        })->map(fn ($key) => trim((string) $key))
            ->filter()
            ->unique()
            ->values();

        return response()->json([
            'item_keys' => $itemKeys,
        ]);
    }

    public function show(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        return response()->json(['payment' => $payment->load('purchase')]);
    }

    public function checkBakong(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 403);
        abort_unless($payment->method === 'bakong', 422, 'This payment is not a Bakong payment.');

        $result = $this->paymentService->checkBakong($payment);

        if (($result['success'] ?? null) === false) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'The Bakong payment could not be verified.',
            ], (int) ($result['http_status'] ?? 502));
        }

        return response()->json($result, $result['paid'] ? 200 : 202);
    }
}
