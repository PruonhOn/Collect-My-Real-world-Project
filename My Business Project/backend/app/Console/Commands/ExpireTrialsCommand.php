<?php

namespace App\Console\Commands;

use App\Models\BusinessSubscription;
use App\Services\Billing\SubscriptionService;
use Illuminate\Console\Command;

/**
 * Ends trials that were never converted into a paid subscription.
 *
 * A trial is only ended when its recorded end date has passed. The command is
 * safe to rerun.
 */
class ExpireTrialsCommand extends Command
{
    protected $signature = 'subscriptions:expire-trials';

    protected $description = 'End trials that were not converted into a paid subscription';

    public function handle(SubscriptionService $subscriptions): int
    {
        $expired = 0;

        BusinessSubscription::withoutGlobalScopes()
            ->where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->chunkById(100, function ($chunk) use ($subscriptions, &$expired) {
                foreach ($chunk as $subscription) {
                    $business = $subscription->business;

                    if (! $business) {
                        continue;
                    }

                    $subscriptions->expire($business, null, 'Trial ended without a confirmed payment');
                    $expired++;
                }
            });

        $this->info("Ended {$expired} trial(s).");

        return self::SUCCESS;
    }
}
