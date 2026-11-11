<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public const TAX_RATE = 0.10;

    public function checkout(User $user, array $bookIds): Order
    {
        $ownedSlugs = $user->ownedBooks()->pluck('books.slug')->flip();

        $books = Book::query()
            ->published()
            ->where('type', Book::TYPE_PAID)
            ->whereIn('id', $bookIds)
            ->with(['translations', 'authors'])
            ->get();

        $purchasable = $books->reject(fn (Book $book) => $ownedSlugs->has($book->slug));

        if ($purchasable->isEmpty()) {
            throw ValidationException::withMessages([
                'book_ids' => 'The cart does not contain any purchasable books.',
            ]);
        }

        $subtotal = $purchasable->sum(fn (Book $book) => (float) $book->price);
        $tax = round($subtotal * self::TAX_RATE, 2);
        $total = round($subtotal + $tax, 2);

        return DB::transaction(function () use ($user, $purchasable, $subtotal, $tax, $total) {
            $order = $user->orders()->create([
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => $tax,
                'total' => $total,
                'status' => Order::STATUS_PENDING,
            ]);

            $purchasable->each(function (Book $book) use ($order) {
                $order->items()->create([
                    'book_id' => $book->id,
                    'book_title' => $book->translation()?->title ?? $book->slug,
                    'price' => $book->price,
                    'quantity' => 1,
                ]);
            });

            return $order;
        });
    }

    public function pay(Order $order, string $paymentMethod): Order
    {
        if ($order->isPaid()) {
            return $order;
        }

        if ($order->status !== Order::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'order' => 'Only pending orders can be paid.',
            ]);
        }

        $transactionId = 'MOCK-'.strtoupper(uniqid());

        return DB::transaction(function () use ($order, $paymentMethod, $transactionId) {
            $order->forceFill([
                'status' => Order::STATUS_PAID,
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId,
                'paid_at' => now(),
            ])->save();

            $order->load('items');

            $order->items->each(function (OrderItem $item) use ($order, $paymentMethod, $transactionId) {
                $order->user->ownedBooks()->syncWithoutDetaching([
                    $item->book_id => [
                        'amount' => $item->price,
                        'payment_method' => $paymentMethod,
                        'transaction_id' => $transactionId,
                    ],
                ]);
            });

            return $order;
        });
    }
}
