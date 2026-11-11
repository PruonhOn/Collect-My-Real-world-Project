<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Purchase;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    private const PAYMENT_METHODS = ['aba', 'acleda', 'wing', 'bakong', 'stripe', 'mock'];

    public function __construct(private PaymentGatewayService $paymentService) {}

    public function purchase(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->is_published, 404);

        if ($book->is_free) {
            return response()->json(['message' => 'This book is free. No payment required.'], 422);
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

        $result = $this->paymentService->checkout($purchase, [
            'method' => $method,
            'book_slug' => $book->slug,
            'item_name' => $book->title,
            'mock_approve' => $request->input('mock_approve', true),
        ]);

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

    /**
     * @param  array{status: string, provider_ref: string|null, checkout_url: string|null, payment: Payment}  $result
     */
    private function checkoutResponse(array $result): JsonResponse
    {
        $payment = $result['payment'];

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

    public function show(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        return response()->json(['payment' => $payment->load('purchase')]);
    }
}
