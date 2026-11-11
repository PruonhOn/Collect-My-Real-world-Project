<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Models\VipPlan;
use App\Models\VipSubscription;

class NotificationService
{
    public function orderPaid(Order $order): Notification
    {
        $bookCount = $order->items->count();
        $bookTitles = $order->items->pluck('book_title')->take(2)->implode(', ');

        return $order->user->notifications()->create([
            'type' => Notification::TYPE_ORDER_PAID,
            'title' => 'Order paid',
            'message' => $bookCount > 1
                ? "Your order #{$order->order_number} for {$bookTitles} and more has been paid."
                : "Your order #{$order->order_number} for {$bookTitles} has been paid.",
            'data' => [
                'order_number' => $order->order_number,
                'total' => (float) $order->total,
                'book_count' => $bookCount,
            ],
        ]);
    }

    public function vipSubscribed(User $user, VipPlan $plan, VipSubscription $subscription): Notification
    {
        $planName = $plan->translation()?->title ?? $plan->name;

        return $user->notifications()->create([
            'type' => Notification::TYPE_VIP_SUBSCRIBED,
            'title' => 'VIP activated',
            'message' => "Your {$planName} subscription is now active until {$subscription->ends_at?->toDateString()}.",
            'data' => [
                'plan_name' => $planName,
                'plan_id' => $plan->id,
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ],
        ]);
    }

    public function reviewApproved(Review $review): Notification
    {
        $bookTitle = $review->book?->translation()?->title ?? $review->book?->slug;

        return $review->user->notifications()->create([
            'type' => Notification::TYPE_REVIEW_APPROVED,
            'title' => 'Review approved',
            'message' => "Your review of {$bookTitle} has been approved and is now public.",
            'data' => [
                'book_id' => $review->book_id,
                'book_title' => $bookTitle,
                'rating' => $review->rating,
            ],
        ]);
    }

    public function reviewRejected(Review $review): Notification
    {
        $bookTitle = $review->book?->translation()?->title ?? $review->book?->slug;

        return $review->user->notifications()->create([
            'type' => Notification::TYPE_REVIEW_REJECTED,
            'title' => 'Review rejected',
            'message' => "Your review of {$bookTitle} was not approved.",
            'data' => [
                'book_id' => $review->book_id,
                'book_title' => $bookTitle,
            ],
        ]);
    }
}
