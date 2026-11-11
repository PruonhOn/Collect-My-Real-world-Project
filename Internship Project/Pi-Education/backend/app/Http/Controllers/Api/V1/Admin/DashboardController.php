<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Models\VipSubscription;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $revenue = (float) Order::where('status', Order::STATUS_PAID)->sum('total');

        return $this->success([
            'counters' => [
                'total_books' => Book::withTrashed()->count(),
                'published_books' => Book::published()->count(),
                'total_users' => User::count(),
                'active_vip_users' => VipSubscription::where('status', VipSubscription::STATUS_ACTIVE)
                    ->where('ends_at', '>', now())
                    ->distinct('user_id')
                    ->count('user_id'),
                'total_orders' => Order::count(),
                'paid_orders' => Order::where('status', Order::STATUS_PAID)->count(),
                'revenue' => $revenue,
                'pending_reviews' => Review::where('status', Review::STATUS_PENDING)->count(),
            ],
            'recent_orders' => Order::query()
                ->with(['items', 'user'])
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(function (Order $order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'user_email' => $order->user?->email,
                        'status' => $order->status,
                        'total' => (float) $order->total,
                        'created_at' => $order->created_at?->toIso8601String(),
                    ];
                }),
        ], 'Dashboard stats retrieved.');
    }
}
