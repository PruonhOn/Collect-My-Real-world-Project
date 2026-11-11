<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Purchase;

/**
 * Runs side effects once a purchase becomes paid/activated:
 * purchase counters on books and VIP membership activation.
 */
class PurchaseActivationService
{
    public function activated(Purchase $purchase): void
    {
        if ($purchase->book_id !== null) {
            Book::whereKey($purchase->book_id)->increment('purchase_count');
        }

        if ($purchase->course_id !== null) {
            $purchase->course?->increment('purchase_count');
        }

        if (VipService::isVipPurchase($purchase)) {
            $plan = VipService::planForItemKey($purchase->external_item_key);
            if ($plan !== null) {
                VipService::activate($purchase->user, $plan);
            }
        }
    }
}
