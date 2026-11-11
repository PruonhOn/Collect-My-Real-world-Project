<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Purchase;
use Illuminate\Http\Request;

class AdminPurchaseController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Purchase::with(['user:id,name,email', 'book', 'payment']);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $purchases = $query->latest()->paginate($request->per_page ?? 15);

        $mapped = $purchases->getCollection()->map(function ($purchase) {
            return [
                'id' => $purchase->id,
                'user' => [
                    'id' => $purchase->user->id,
                    'name' => $purchase->user->name,
                    'email' => $purchase->user->email,
                ],
                'book' => [
                    'id' => $purchase->book->id,
                    'slug' => $purchase->book->slug,
                    'title' => $purchase->book->title,
                    'cover_image' => $purchase->book->cover_image ? url('/storage/'.$purchase->book->cover_image) : null,
                ],
                'amount' => (float) $purchase->amount,
                'status' => $purchase->status,
                'payment_reference' => $purchase->payment?->reference,
                'payment_method' => $purchase->payment?->method,
                'payment_status' => $purchase->payment?->status,
                'created_at' => $purchase->created_at->toDateTimeString(),
            ];
        });

        return $this->success([
            'purchases' => $mapped,
            'pagination' => [
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'per_page' => $purchases->perPage(),
                'total' => $purchases->total(),
            ],
        ]);
    }

    public function approve(int $id)
    {
        $purchase = Purchase::with('payment')->find($id);

        if (! $purchase) {
            return $this->error('Purchase not found', 404);
        }

        if ($purchase->status === 'paid') {
            return $this->error('Purchase is already paid', 422);
        }

        $purchase->update(['status' => 'paid']);

        if ($purchase->payment) {
            $purchase->payment->update(['status' => 'paid']);
        }

        return $this->success(null, 'Purchase approved successfully');
    }

    public function refund(int $id)
    {
        $purchase = Purchase::with('payment')->find($id);

        if (! $purchase) {
            return $this->error('Purchase not found', 404);
        }

        if ($purchase->status !== 'paid') {
            return $this->error('Only paid purchases can be refunded', 422);
        }

        $purchase->update(['status' => 'refunded']);

        if ($purchase->payment) {
            $purchase->payment->update(['status' => 'failed']);
        }

        return $this->success(null, 'Purchase refunded');
    }
}