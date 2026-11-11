<?php

namespace App\Console\Commands;

use App\Models\BusinessSubscription;
use App\Services\Billing\SubscriptionService;
use Illuminate\Console\Command;

/**
 * Expires subscriptions whose paid period has ended.
 *
 * The command is idempotent: running it twice never changes the outcome and
 * it never expires a subscription that the payment provider still considers
 * active (that is the job of subscriptions:reconcile).
 */
class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire subscriptions whose paid period has ended';

    public function handle(SubscriptionService $subscriptions): int
    {
        $expired = 0;

        BusinessSubscription::withoutGlobalScopes()
            ->whereIn('status', ['active', 'canceled', 'trialing'])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->chunkById(100, function ($chunk) use ($subscriptions, &$expired) {
                foreach ($chunk as $subscription) {
                    $business = $subscription->business;

                    if (! $business) {
                        continue;
                    }

                    $subscriptions->expire($business, null, 'Paid period ended');
                    $expired++;
                }
            });

        $this->info("Expired {$expired} subscription(s).");

        return self::SUCCESS;
    }
}
