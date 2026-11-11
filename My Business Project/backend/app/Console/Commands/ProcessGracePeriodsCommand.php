<?php

namespace App\Console\Commands;

use App\Models\BusinessSubscription;
use App\Services\Billing\SubscriptionService;
use Illuminate\Console\Command;

/**
 * Moves past-due subscriptions into the expired state once their grace
 * period has elapsed. The grace period keeps the paid entitlements alive for
 * a configurable number of days while the platform retries collection.
 */
class ProcessGracePeriodsCommand extends Command
{
    protected $signature = 'subscriptions:grace-period';

    protected $description = 'Expire subscriptions whose payment grace period has ended';

    public function handle(SubscriptionService $subscriptions): int
    {
        $expired = 0;

        BusinessSubscription::withoutGlobalScopes()
            ->where('status', 'past_due')
            ->whereNotNull('grace_ends_at')
            ->where('grace_ends_at', '<=', now())
            ->chunkById(100, function ($chunk) use ($subscriptions, &$expired) {
                foreach ($chunk as $subscription) {
                    $business = $subscription->business;

                    if (! $business) {
                        continue;
                    }

                    $subscriptions->expire($business, null, 'Grace period ended');
                    $expired++;
                }
            });

        $this->info("Expired {$expired} past-due subscription(s).");

        return self::SUCCESS;
    }
}
