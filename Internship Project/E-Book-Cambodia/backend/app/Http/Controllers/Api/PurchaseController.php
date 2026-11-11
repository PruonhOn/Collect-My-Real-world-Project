<?php

namespace App\Http\Controllers\Api;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Purchase;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'book_id' => ['required', 'exists:books,id'],
            'payment_method' => ['required', 'string', 'in:bakong,mock'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $book = Book::where('id', $request->book_id)->where('is_published', true)->first();

        if (! $book) {
            return $this->error('Book not found', 404);
        }

        if ($book->isFree()) {
            return $this->error('This book is free. No payment required.', 400);
        }

        $user = $request->user();

        if ($user->hasPurchased($book)) {
            return $this->error('You have already purchased this book.', 400);
        }

        $existingPending = Purchase::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            return $this->success([
                'purchase' => $existingPending,
                'payment' => $existingPending->payment,
            ], 'You have a pending purchase for this book.');
        }

        DB::beginTransaction();

        try {
            $purchase = Purchase::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'amount' => $book->price,
                'status' => 'pending',
            ]);

            $gatewayService = new PaymentGatewayService();
            $result = $gatewayService->createPayment($purchase, $request->payment_method);

            DB::commit();

            return $this->success([
                'purchase' => $purchase,
                'payment' => $result['payment'],
                'checkout_url' => $result['checkout_url'] ?? null,
                'qr_code' => $result['qr_code'] ?? null,
            ], 'Purchase initiated. Please complete payment.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return $this->error('Failed to initiate purchase. Please try again.', 500);
        }
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $purchases = Purchase::with(['book', 'book.translations', 'book.category', 'payment'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($purchase) {
                $book = $purchase->book;

                return [
                    'id' => $purchase->id,
                    'amount' => (float) $purchase->amount,
                    'status' => $purchase->status,
                    'created_at' => $purchase->created_at->toDateTimeString(),
                    'book' => [
                        'id' => $book->id,
                        'slug' => $book->slug,
                        'title' => $book->title,
                        'cover_image' => $book->cover_image ? url('/storage/'.$book->cover_image) : null,
                        'is_free' => $book->is_free,
                    ],
                    'payment' => $purchase->payment ? [
                        'reference' => $purchase->payment->reference,
                        'method' => $purchase->payment->method,
                        'status' => $purchase->payment->status,
                        'provider_ref' => $purchase->payment->provider_ref,
                    ] : null,
                ];
            });

        return $this->success($purchases);
    }
}
